#!/usr/bin/env python3
"""Create optimized, non-destructive WebP copies for the curated pricelist CSV."""
from __future__ import annotations

import argparse
import csv
import hashlib
import subprocess
import tempfile
from pathlib import Path

from PIL import Image, ImageOps

ROOT = Path(__file__).resolve().parents[1]
CSV_PATH = ROOT / "database" / "pricelist_import.csv"
DEST = ROOT / "bouquet" / "assets" / "images"
SUPPORTED = {".jpg", ".jpeg", ".png", ".webp", ".heic"}


def ffmpeg_path() -> str:
    try:
        import imageio_ffmpeg
    except ImportError as exc:
        raise RuntimeError("Install imageio-ffmpeg to convert source HEIC photos.") from exc
    return imageio_ffmpeg.get_ffmpeg_exe()


def listed_sources() -> list[Path]:
    paths: set[Path] = set()
    with CSV_PATH.open(encoding="utf-8-sig", newline="") as stream:
        for row in csv.DictReader(stream):
            values = [row.get("source_image", ""), *row.get("gallery_images", "").split("|")]
            paths.update(Path(value.strip()) for value in values if value.strip())
    resolved: list[Path] = []
    for relative in sorted(paths):
        source = (ROOT / relative).resolve()
        if not source.is_relative_to(ROOT.resolve()):
            raise ValueError(f"Source escapes project root: {relative}")
        if not source.is_file():
            raise FileNotFoundError(f"Pricelist image not found: {relative}")
        if source.suffix.lower() not in SUPPORTED:
            raise ValueError(f"Unsupported image type: {relative}")
        resolved.append(source)
    return resolved


def trim_pdf_border(image: Image.Image) -> Image.Image:
    """Crop uniform near-black borders left by PDF image extraction.

    Only used for images extracted from the PDF, never for shop photos.
    """
    rgb = image.convert("RGB")
    width, height = rgb.size
    pixels = rgb.load()
    sample_x = range(0, width, 4)
    sample_y = range(0, height, 4)

    def row_mean(y: int) -> float:
        return sum(sum(pixels[x, y]) / 3 for x in sample_x) / len(sample_x)

    def col_mean(x: int) -> float:
        return sum(sum(pixels[x, y]) / 3 for y in sample_y) / len(sample_y)

    # The PDF-extracted add-on photos sit on a white background, so uniform
    # dark/grey edge rows are extraction artifacts, not product content.
    limit_y = int(height * 0.05)
    limit_x = int(width * 0.05)
    top = bottom = left = right = 0
    while top < limit_y and row_mean(top) < 235:
        top += 1
    while bottom < limit_y and row_mean(height - 1 - bottom) < 235:
        bottom += 1
    while left < limit_x and col_mean(left) < 235:
        left += 1
    while right < limit_x and col_mean(width - 1 - right) < 235:
        right += 1
    if top or bottom or left or right:
        return rgb.crop((left, top, width - right, height - bottom))
    return rgb


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--force", action="store_true", help="Rebuild WebP files even if they already exist.")
    args = parser.parse_args()

    sources = listed_sources()
    DEST.mkdir(parents=True, exist_ok=True)
    created = reused = 0
    input_bytes = output_bytes = 0
    with tempfile.TemporaryDirectory(prefix="bj-price-media-") as scratch_name:
        scratch = Path(scratch_name)
        for source in sources:
            digest = hashlib.sha256(source.read_bytes()).hexdigest()[:16]
            target = DEST / f"catalog-{digest}.webp"
            input_bytes += source.stat().st_size
            if target.exists() and not args.force:
                reused += 1
                output_bytes += target.stat().st_size
                continue

            if source.suffix.lower() == ".heic":
                decoded = scratch / f"{digest}.png"
                # HEIC files can expose auxiliary preview streams. Without an
                # explicit map, ffmpeg decodes the full primary image instead
                # of the 512x512 thumbnail stream.
                subprocess.run([
                    ffmpeg_path(), "-hide_banner", "-loglevel", "error", "-y", "-i", str(source),
                    "-frames:v", "1", str(decoded),
                ], check=True)
                image_path = decoded
            else:
                image_path = source

            with Image.open(image_path) as opened:
                image = ImageOps.exif_transpose(opened).convert("RGB")
                if "extracted-addons" in source.parts:
                    image = trim_pdf_border(image)
                if image.width > 1600:
                    height = round(image.height * 1600 / image.width)
                    resized = image.resize((1600, height), Image.Resampling.LANCZOS)
                    image.close()
                    image = resized
                image.save(target, "WEBP", quality=88, method=6)
                image.close()
            created += 1
            output_bytes += target.stat().st_size

    print(f"Pricelist source images checked: {len(sources)} ({input_bytes:,} bytes)")
    print(f"WebP assets: {created} created, {reused} reused ({output_bytes:,} bytes)")
    print("Original source photos were not modified.")


if __name__ == "__main__":
    main()
