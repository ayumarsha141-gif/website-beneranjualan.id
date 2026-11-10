#!/usr/bin/env python3
"""Generate responsive WebP derivatives without altering source product photos.

Requires Pillow (`python -m pip install Pillow`). Run from the repository root:
    python scripts/optimize_images.py
"""
from pathlib import Path
from PIL import Image, ImageOps


ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "bouquet" / "assets" / "images"
DEST = SOURCE / "optimized"
WIDTHS = (480, 960)
SUPPORTED = {".jpg", ".jpeg", ".png", ".webp"}


def main() -> None:
    DEST.mkdir(parents=True, exist_ok=True)
    saved = 0
    for path in sorted(SOURCE.iterdir()):
        if not path.is_file() or path.suffix.lower() not in SUPPORTED:
            continue
        with Image.open(path) as original:
            image = ImageOps.exif_transpose(original).convert("RGB")
            for width in WIDTHS:
                if image.width <= width:
                    resized = image.copy()
                else:
                    height = round(image.height * width / image.width)
                    resized = image.resize((width, height), Image.Resampling.LANCZOS)
                target = DEST / f"{path.stem}-{width}.webp"
                resized.save(target, "WEBP", quality=82, method=6)
                saved += 1
                resized.close()
    print(f"Generated {saved} responsive WebP files in {DEST}")


if __name__ == "__main__":
    main()
