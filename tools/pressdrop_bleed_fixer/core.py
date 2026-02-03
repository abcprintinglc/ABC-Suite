from __future__ import annotations

import io
import copy
import json
import os
import re
from dataclasses import dataclass
from typing import Dict, List, Optional, Tuple

from PIL import Image
from pypdf import PdfReader, PdfWriter
from pypdf._page import PageObject
from pypdf.generic import RectangleObject
from pypdf import Transformation


POINTS_PER_INCH = 72.0
MM_PER_INCH = 25.4


@dataclass(frozen=True)
class Rect:
    """PDF coordinate rectangle (origin bottom-left)."""

    x0: float
    y0: float
    x1: float
    y1: float

    @property
    def width(self) -> float:
        return float(self.x1 - self.x0)

    @property
    def height(self) -> float:
        return float(self.y1 - self.y0)


def to_points(value: float, unit: str) -> float:
    """Convert inches/mm/pt to PDF points."""
    unit = unit.lower().strip()
    if unit in ("in", "inch", "inches"):
        return float(value) * POINTS_PER_INCH
    if unit in ("mm", "millimeter", "millimeters"):
        return float(value) * POINTS_PER_INCH / MM_PER_INCH
    if unit in ("pt", "pts", "point", "points"):
        return float(value)
    raise ValueError(f"Unsupported unit: {unit}")


def parse_size(spec: str) -> Tuple[float, float, str]:
    """Parse sizes like '4x6in', '101.6x152.4mm', '288x432pt'."""
    m = re.match(r"^\s*([0-9.]+)\s*[xX]\s*([0-9.]+)\s*([a-zA-Z]+)\s*$", spec)
    if not m:
        raise ValueError(f"Invalid size format: {spec}")
    w = float(m.group(1))
    h = float(m.group(2))
    unit = m.group(3)
    return w, h, unit


def parse_bleed(spec: str, unit: str) -> Dict[str, float]:
    """Parse bleed like '0.125' or '0.125,0.125,0.125,0.125' (t,r,b,l) in the same unit."""
    parts = [p.strip() for p in str(spec).split(",") if p.strip()]
    if len(parts) == 1:
        v = float(parts[0])
        return {"top": v, "right": v, "bottom": v, "left": v, "unit": unit}
    if len(parts) == 4:
        t, r, b, l = map(float, parts)
        return {"top": t, "right": r, "bottom": b, "left": l, "unit": unit}
    raise ValueError("Bleed must be 1 value or 4 values (top,right,bottom,left)")


def parse_page_range(rng: str, max_pages: int) -> List[int]:
    """Return a list of 0-based page indexes from 'all', '1', '1-3,7'."""
    rng = (rng or "all").strip().lower()
    if rng in ("all", "*"):
        return list(range(max_pages))
    out: List[int] = []
    for chunk in rng.split(","):
        chunk = chunk.strip()
        if not chunk:
            continue
        if "-" in chunk:
            a, b = chunk.split("-", 1)
            start = int(a)
            end = int(b)
            for p in range(start, end + 1):
                if 1 <= p <= max_pages:
                    out.append(p - 1)
        else:
            p = int(chunk)
            if 1 <= p <= max_pages:
                out.append(p - 1)
    # dedupe while preserving order
    seen = set()
    ordered: List[int] = []
    for p in out:
        if p not in seen:
            seen.add(p)
            ordered.append(p)
    return ordered


def _anchor_offsets(anchor: str) -> Tuple[float, float]:
    """Anchor expressed as offsets in [0..1] for x and y where 0=left/bottom, 1=right/top."""
    a = (anchor or "center").lower().strip()
    mapping = {
        "center": (0.5, 0.5),
        "top": (0.5, 1.0),
        "bottom": (0.5, 0.0),
        "left": (0.0, 0.5),
        "right": (1.0, 0.5),
        "top_left": (0.0, 1.0),
        "top_right": (1.0, 1.0),
        "bottom_left": (0.0, 0.0),
        "bottom_right": (1.0, 0.0),
    }
    return mapping.get(a, (0.5, 0.5))


def _rect_from_pypdf_box(box: RectangleObject) -> Rect:
    return Rect(float(box.left), float(box.bottom), float(box.right), float(box.top))


def pick_pdf_box(page: PageObject, box: str = "auto") -> Rect:
    """Choose which PDF box to treat as the page content bounds."""
    b = (box or "auto").lower().strip()

    def safe_get(attr: str) -> Optional[Rect]:
        try:
            bx = getattr(page, attr)
            if bx is None:
                return None
            r = _rect_from_pypdf_box(bx)
            if r.width > 0 and r.height > 0:
                return r
        except Exception:
            return None
        return None

    if b == "trim":
        r = safe_get("trimbox")
        if r:
            return r
    if b == "bleed":
        r = safe_get("bleedbox")
        if r:
            return r
    if b == "crop":
        r = safe_get("cropbox")
        if r:
            return r
    if b == "media":
        r = safe_get("mediabox")
        if r:
            return r

    # auto: prefer TrimBox, then CropBox, then MediaBox
    for attr in ("trimbox", "cropbox", "mediabox"):
        r = safe_get(attr)
        if r:
            return r
    # last-resort default
    return _rect_from_pypdf_box(page.mediabox)


def compute_boxes(trim_w_pt: float, trim_h_pt: float, bleed: Dict[str, float]) -> Tuple[Rect, Rect, Rect]:
    """Return (MediaBox, BleedBox, TrimBox) rects in output page coordinates (PDF coords)."""
    bu = bleed.get("unit", "in")
    bt = to_points(float(bleed["top"]), bu)
    br = to_points(float(bleed["right"]), bu)
    bb = to_points(float(bleed["bottom"]), bu)
    bl = to_points(float(bleed["left"]), bu)

    media_w = trim_w_pt + bl + br
    media_h = trim_h_pt + bt + bb

    media = Rect(0, 0, media_w, media_h)
    bleed_box = media
    # In PDF coords, bottom bleed is bb, left bleed is bl.
    trim_box = Rect(bl, bb, bl + trim_w_pt, bb + trim_h_pt)
    return media, bleed_box, trim_box


def crop_rect_for_cover(src_rect: Rect, dest_rect: Rect, anchor: str) -> Rect:
    """Crop src_rect so its aspect matches dest_rect for "cover" fitting."""
    sw = src_rect.width
    sh = src_rect.height
    dw = dest_rect.width
    dh = dest_rect.height
    if sw <= 0 or sh <= 0 or dw <= 0 or dh <= 0:
        return src_rect

    src_ar = sw / sh
    dest_ar = dw / dh
    ax, ay = _anchor_offsets(anchor)
    if abs(src_ar - dest_ar) < 1e-6:
        return src_rect

    if src_ar > dest_ar:
        # source wider -> crop width
        new_w = sh * dest_ar
        x0 = src_rect.x0 + (sw - new_w) * ax
        return Rect(x0, src_rect.y0, x0 + new_w, src_rect.y1)
    else:
        # source taller -> crop height
        new_h = sw / dest_ar
        y0 = src_rect.y0 + (sh - new_h) * ay
        return Rect(src_rect.x0, y0, src_rect.x1, y0 + new_h)


def _rect_to_box(r: Rect) -> RectangleObject:
    return RectangleObject((r.x0, r.y0, r.x1, r.y1))


def _compute_transform(src: Rect, dest: Rect, mode: str, anchor: str) -> Transformation:
    """Compute a transformation mapping src rect into dest rect."""
    sw, sh = src.width, src.height
    dw, dh = dest.width, dest.height
    if sw <= 0 or sh <= 0:
        return Transformation()

    ax, ay = _anchor_offsets(anchor)
    mode = (mode or "fit_trim_proportional").lower().strip()

    if mode in ("stretch_trim", "stretch_bleed"):
        sx = dw / sw
        sy = dh / sh
        # place src lower-left at dest lower-left
        tx = dest.x0 - src.x0 * sx
        ty = dest.y0 - src.y0 * sy
        return Transformation().scale(sx=sx, sy=sy).translate(tx=tx, ty=ty)

    # proportional
    s_fit = min(dw / sw, dh / sh)
    s_fill = max(dw / sw, dh / sh)
    s = s_fit
    if mode in ("fill_bleed_proportional", "fill_trim_proportional"):
        s = s_fill

    content_w = sw * s
    content_h = sh * s
    extra_x = dw - content_w
    extra_y = dh - content_h
    # anchor within dest rect
    tx = dest.x0 + extra_x * ax - src.x0 * s
    ty = dest.y0 + extra_y * ay - src.y0 * s
    return Transformation().scale(sx=s, sy=s).translate(tx=tx, ty=ty)



def _compute_transform_stretch(src: Rect, dest: Rect, mirror_x: bool = False, mirror_y: bool = False) -> Transformation:
    """Compute a stretch transform from src->dest, optionally mirroring along axes.

    mirror_x flips horizontally (src.x0 maps to dest.x1, src.x1 maps to dest.x0).
    mirror_y flips vertically   (src.y0 maps to dest.y1, src.y1 maps to dest.y0).
    """
    sw, sh = src.width, src.height
    dw, dh = dest.width, dest.height
    if sw <= 0 or sh <= 0:
        return Transformation()

    sx = (dw / sw) * (-1.0 if mirror_x else 1.0)
    sy = (dh / sh) * (-1.0 if mirror_y else 1.0)

    if mirror_x:
        tx = dest.x1 - sx * src.x0
    else:
        tx = dest.x0 - sx * src.x0

    if mirror_y:
        ty = dest.y1 - sy * src.y0
    else:
        ty = dest.y0 - sy * src.y0

    return Transformation().scale(sx=sx, sy=sy).translate(tx=tx, ty=ty)


def _place_pdf_page_return_clip(
    out_page: PageObject,
    src_page: PageObject,
    dest_rect: Rect,
    fit_mode: str,
    anchor: str,
    pdf_box: str,
) -> Rect:
    """Place a PDF page and return the clip rect used in source coordinates."""
    src_rect = pick_pdf_box(src_page, pdf_box)
    mode = (fit_mode or "fit_trim_proportional").lower().strip()

    clip = src_rect
    if mode in ("fill_bleed_proportional", "fill_trim_proportional"):
        clip = crop_rect_for_cover(src_rect, dest_rect, anchor)

    page_copy = copy.copy(src_page)
    page_copy.mediabox = _rect_to_box(clip)
    page_copy.cropbox = _rect_to_box(clip)

    transform = _compute_transform(
        clip,
        dest_rect,
        "stretch_bleed" if mode in ("stretch_trim", "stretch_bleed") else mode,
        anchor,
    )
    out_page.merge_transformed_page(page_copy, transform)
    return clip


def _edge_extend_bleed(
    out_page: PageObject,
    src_page: PageObject,
    clip: Rect,
    trim_box: Rect,
    bleed_box: Rect,
    mode: str,
) -> None:
    """Fill bleed area by extending edges from the placed clip.

    mode: 'mirror' or 'smear'
    Note: This keeps the trim area untouched; only the bleed margins are filled.
    """
    mode = (mode or "").lower().strip()
    if mode not in ("mirror", "smear"):
        return

    # bleed widths (points)
    l_w = max(trim_box.x0 - bleed_box.x0, 0.0)
    r_w = max(bleed_box.x1 - trim_box.x1, 0.0)
    b_h = max(trim_box.y0 - bleed_box.y0, 0.0)
    t_h = max(bleed_box.y1 - trim_box.y1, 0.0)
    if l_w == r_w == b_h == t_h == 0.0:
        return

    # choose a thin slice from the source clip to stretch/mirror into bleed
    slice_w = max(min(clip.width * 0.02, 18.0), 3.0)
    slice_h = max(min(clip.height * 0.02, 18.0), 3.0)

    def place_slice(src_slice: Rect, dest_slice: Rect, mx: bool, my: bool):
        page_copy = copy.copy(src_page)
        page_copy.mediabox = _rect_to_box(src_slice)
        page_copy.cropbox = _rect_to_box(src_slice)
        if mode == "mirror":
            transform = _compute_transform_stretch(src_slice, dest_slice, mirror_x=mx, mirror_y=my)
        else:
            transform = _compute_transform_stretch(src_slice, dest_slice, mirror_x=False, mirror_y=False)
        out_page.merge_transformed_page(page_copy, transform)

    # Left / Right strips
    if l_w > 0:
        src_slice = Rect(clip.x0, clip.y0, clip.x0 + slice_w, clip.y1)
        dest_slice = Rect(bleed_box.x0, trim_box.y0, trim_box.x0, trim_box.y1)
        place_slice(src_slice, dest_slice, mx=True, my=False)

    if r_w > 0:
        src_slice = Rect(clip.x1 - slice_w, clip.y0, clip.x1, clip.y1)
        dest_slice = Rect(trim_box.x1, trim_box.y0, bleed_box.x1, trim_box.y1)
        place_slice(src_slice, dest_slice, mx=True, my=False)

    # Bottom / Top strips
    if b_h > 0:
        src_slice = Rect(clip.x0, clip.y0, clip.x1, clip.y0 + slice_h)
        dest_slice = Rect(trim_box.x0, bleed_box.y0, trim_box.x1, trim_box.y0)
        place_slice(src_slice, dest_slice, mx=False, my=True)

    if t_h > 0:
        src_slice = Rect(clip.x0, clip.y1 - slice_h, clip.x1, clip.y1)
        dest_slice = Rect(trim_box.x0, trim_box.y1, trim_box.x1, bleed_box.y1)
        place_slice(src_slice, dest_slice, mx=False, my=True)

    # Corners (only if both directions exist)
    if l_w > 0 and b_h > 0:
        src_slice = Rect(clip.x0, clip.y0, clip.x0 + slice_w, clip.y0 + slice_h)
        dest_slice = Rect(bleed_box.x0, bleed_box.y0, trim_box.x0, trim_box.y0)
        place_slice(src_slice, dest_slice, mx=True, my=True)

    if r_w > 0 and b_h > 0:
        src_slice = Rect(clip.x1 - slice_w, clip.y0, clip.x1, clip.y0 + slice_h)
        dest_slice = Rect(trim_box.x1, bleed_box.y0, bleed_box.x1, trim_box.y0)
        place_slice(src_slice, dest_slice, mx=True, my=True)

    if l_w > 0 and t_h > 0:
        src_slice = Rect(clip.x0, clip.y1 - slice_h, clip.x0 + slice_w, clip.y1)
        dest_slice = Rect(bleed_box.x0, trim_box.y1, trim_box.x0, bleed_box.y1)
        place_slice(src_slice, dest_slice, mx=True, my=True)

    if r_w > 0 and t_h > 0:
        src_slice = Rect(clip.x1 - slice_w, clip.y1 - slice_h, clip.x1, clip.y1)
        dest_slice = Rect(trim_box.x1, trim_box.y1, bleed_box.x1, bleed_box.y1)
        place_slice(src_slice, dest_slice, mx=True, my=True)



def _draw_crop_marks_on_page(page: PageObject, trim_box: Rect, bleed_box: Rect) -> None:
    """Draw simple crop marks as PDF vector commands."""
    left_bleed = trim_box.x0 - bleed_box.x0
    right_bleed = bleed_box.x1 - trim_box.x1
    bottom_bleed = trim_box.y0 - bleed_box.y0
    top_bleed = bleed_box.y1 - trim_box.y1

    tick = min(max(min(left_bleed, right_bleed, bottom_bleed, top_bleed) * 0.66, 6), 18)
    lw = 0.25

    x0, y0, x1, y1 = trim_box.x0, trim_box.y0, trim_box.x1, trim_box.y1

    lines = [
        # horizontal
        (x0 - tick, y0, x0, y0),
        (x1, y0, x1 + tick, y0),
        (x0 - tick, y1, x0, y1),
        (x1, y1, x1 + tick, y1),
        # vertical
        (x0, y0 - tick, x0, y0),
        (x1, y0 - tick, x1, y0),
        (x0, y1, x0, y1 + tick),
        (x1, y1, x1, y1 + tick),
    ]

    # PDF content stream
    parts = [
        "q\n",
        "0 0 0 RG\n",  # stroke color
        f"{lw} w\n",
    ]
    for xA, yA, xB, yB in lines:
        parts.append(f"{xA:.4f} {yA:.4f} m {xB:.4f} {yB:.4f} l S\n")
    parts.append("Q\n")
    content = "".join(parts).encode("ascii")
    page.add_content(content)


def _image_to_single_page_pdf_bytes(img_path: str) -> bytes:
    """Convert a raster image to a 1-page PDF (in memory) using Pillow."""
    img = Image.open(img_path)
    if img.mode not in ("RGB", "L"):
        img = img.convert("RGB")
    bio = io.BytesIO()
    img.save(bio, format="PDF")
    return bio.getvalue()


def _place_pdf_page(
    out_page: PageObject,
    src_page: PageObject,
    dest_rect: Rect,
    fit_mode: str,
    anchor: str,
    pdf_box: str,
) -> None:
    """Place a PDF page onto out_page using pypdf."""
    src_rect = pick_pdf_box(src_page, pdf_box)
    mode = (fit_mode or "fit_trim_proportional").lower().strip()

    # For "fill" (cover), crop source to matching aspect to avoid overflow.
    clip = src_rect
    if mode in ("fill_bleed_proportional", "fill_trim_proportional"):
        clip = crop_rect_for_cover(src_rect, dest_rect, anchor)

    # Create a shallow copy of page with adjusted boxes so pypdf turns it into a form with BBox
    # matching our clip (acts as a clip boundary when merged).
    # pypdf PageObject isn't guaranteed to have a .copy() across versions
    page_copy = copy.copy(src_page)
    page_copy.mediabox = _rect_to_box(clip)
    page_copy.cropbox = _rect_to_box(clip)

    # compute transform from clip->dest
    transform = _compute_transform(clip, dest_rect, "stretch_bleed" if mode in ("stretch_trim", "stretch_bleed") else mode, anchor)
    out_page.merge_transformed_page(page_copy, transform)


def build_press_pdf(job: Dict) -> List[str]:
    """Build press PDFs from the job spec. Returns created PDF paths."""
    layout = job.get("layout", {})
    output = job.get("output", {})
    inputs = job.get("inputs", [])

    if not inputs:
        raise ValueError("No inputs provided")

    trim = layout.get("trim", {})
    unit = trim.get("unit", "in")
    trim_w_pt = to_points(float(trim["w"]), unit)
    trim_h_pt = to_points(float(trim["h"]), unit)

    bleed = layout.get("bleed", {"top": 0, "right": 0, "bottom": 0, "left": 0, "unit": unit})
    if "unit" not in bleed:
        bleed["unit"] = unit

    media_box, bleed_box, trim_box = compute_boxes(trim_w_pt, trim_h_pt, bleed)

    fit_mode = layout.get("fit_mode", "fit_trim_proportional")
    anchor = layout.get("anchor", "center")

    bleed_generator = (layout.get("bleed_generator", "none") or "none").lower().strip()

    # When using edge-extend bleed, we place the main content into trim, then fill bleed margins.
    fit_mode_for_trim = (fit_mode or "fit_trim_proportional").lower().strip()
    if bleed_generator in ("mirror", "smear"):
        if fit_mode_for_trim == "fit_bleed_proportional":
            fit_mode_for_trim = "fit_trim_proportional"
        elif fit_mode_for_trim == "fill_bleed_proportional":
            fit_mode_for_trim = "fill_trim_proportional"
        elif fit_mode_for_trim == "stretch_bleed":
            fit_mode_for_trim = "stretch_trim"
    marks = layout.get("marks", {})
    add_crop_marks = bool(marks.get("crop_marks", False))

    out_dir = output.get("dir", os.getcwd())
    os.makedirs(out_dir, exist_ok=True)
    base = output.get("basename", "output")

    created: List[str] = []

    def dest_for_mode(mode: str) -> Rect:
        m = (mode or "").lower().strip()
        if m.endswith("_bleed") or "bleed" in m:
            return bleed_box
        return trim_box

    # Build one output PDF per input file (simple + matches v0.1 behavior)
    for item in inputs:
        in_path = item["path"]
        in_name = os.path.splitext(os.path.basename(in_path))[0]
        # If the UI already set basename to include the input name, avoid duplicating it.
        if len(inputs) == 1:
            out_path = os.path.join(out_dir, f"{base}.pdf")
        else:
            out_path = os.path.join(out_dir, f"{base}__{in_name}.pdf")

        ext = os.path.splitext(in_path)[1].lower()
        writer = PdfWriter()
        dest_rect = dest_for_mode(fit_mode)

        if ext == ".pdf":
            reader = PdfReader(in_path)
            pages = parse_page_range(item.get("pages", "all"), len(reader.pages))
            pdf_box = item.get("pdf_box", "auto")

            for pno in pages:
                src_page = reader.pages[pno]
                out_page = PageObject.create_blank_page(width=media_box.width, height=media_box.height)
                out_page.mediabox = _rect_to_box(media_box)
                out_page.bleedbox = _rect_to_box(bleed_box)
                out_page.trimbox = _rect_to_box(trim_box)
                out_page.cropbox = _rect_to_box(bleed_box)

                if bleed_generator in ("mirror", "smear"):
                    clip = _place_pdf_page_return_clip(out_page, src_page, trim_box, fit_mode_for_trim, anchor, pdf_box)
                    _edge_extend_bleed(out_page, src_page, clip, trim_box, bleed_box, mode=bleed_generator)
                else:
                    _place_pdf_page(out_page, src_page, dest_rect, fit_mode, anchor, pdf_box)
                if add_crop_marks:
                    _draw_crop_marks_on_page(out_page, trim_box, bleed_box)
                writer.add_page(out_page)

        elif ext in (".png", ".jpg", ".jpeg"):
            # convert raster to single-page PDF then treat as PDF
            pdf_bytes = _image_to_single_page_pdf_bytes(in_path)
            reader = PdfReader(io.BytesIO(pdf_bytes))
            src_page = reader.pages[0]
            out_page = PageObject.create_blank_page(width=media_box.width, height=media_box.height)
            out_page.mediabox = _rect_to_box(media_box)
            out_page.bleedbox = _rect_to_box(bleed_box)
            out_page.trimbox = _rect_to_box(trim_box)
            out_page.cropbox = _rect_to_box(bleed_box)

            if bleed_generator in ("mirror", "smear"):
                clip = _place_pdf_page_return_clip(out_page, src_page, trim_box, fit_mode_for_trim, anchor, pdf_box="media")
                _edge_extend_bleed(out_page, src_page, clip, trim_box, bleed_box, mode=bleed_generator)
            else:
                _place_pdf_page(out_page, src_page, dest_rect, fit_mode, anchor, pdf_box="media")
            if add_crop_marks:
                _draw_crop_marks_on_page(out_page, trim_box, bleed_box)
            writer.add_page(out_page)

        else:
            raise ValueError(f"Unsupported input type: {ext} (supported: pdf, png, jpg, jpeg)")

        with open(out_path, "wb") as f:
            writer.write(f)

        created.append(out_path)

    return created


def write_job_json(job: Dict, path: str) -> None:
    os.makedirs(os.path.dirname(os.path.abspath(path)), exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        json.dump(job, f, indent=2)


def load_presets(preset_path: str) -> Dict[str, Dict]:
    """Load presets.json from the presets folder."""
    with open(preset_path, "r", encoding="utf-8") as f:
        data = json.load(f)
    if not isinstance(data, dict):
        raise ValueError("Presets file must be a JSON object")
    return data


def make_job(
    *,
    input_path: str,
    pages_spec: str,
    pdf_box: str,
    trim_size_spec: str,
    bleed_spec: str,
    fit_mode: str,
    anchor: str,
    bleed_generator: str = "none",
    crop_marks: bool,
    out_dir: str,
    basename: Optional[str] = None,
    emit_job: bool = False,
) -> Dict:
    """Create a job dict compatible with both Python output and InDesign JSX."""
    w, h, unit = parse_size(trim_size_spec)
    bleed_vals = parse_bleed(bleed_spec, unit)

    if basename is None or basename.strip() == "":
        basename = os.path.splitext(os.path.basename(input_path))[0]

    # If input is a PDF, compute page_count so the InDesign JSX can safely handle pages='all'
    input_abs = os.path.abspath(input_path)
    ext = os.path.splitext(input_abs)[1].lower()
    page_count = None
    if ext == ".pdf":
        try:
            reader = PdfReader(input_abs)
            page_count = len(reader.pages)
        except Exception:
            page_count = None
        if page_count and (pages_spec or "").strip().lower() == "all":
            pages_spec = f"1-{page_count}"

    job: Dict = {
        "inputs": [
            {
                "path": input_abs,
                "pages": pages_spec,
                "pdf_box": pdf_box,
                "page_count": page_count,
            }
        ],
        "layout": {
            "trim": {"w": w, "h": h, "unit": unit},
            "bleed": {
                "top": bleed_vals["top"],
                "right": bleed_vals["right"],
                "bottom": bleed_vals["bottom"],
                "left": bleed_vals["left"],
                "unit": unit,
            },
            "fit_mode": fit_mode,
            "anchor": anchor,
            "bleed_generator": (bleed_generator or "none").lower().strip(),
            "auto_rotate": False,
            "marks": {"crop_marks": bool(crop_marks)},
        },
        "output": {
            "dir": os.path.abspath(out_dir),
            "basename": basename,
        },
    }

    if emit_job:
        job_json_path = os.path.join(os.path.abspath(out_dir), f"{basename}.job.json")
        job["output"]["job_json_path"] = job_json_path
        write_job_json(job, job_json_path)

    return job
