#!/usr/bin/env python3
import argparse
import subprocess
import sys
from pathlib import Path


def main() -> int:
    parser = argparse.ArgumentParser(description="Run rembg using the local project runtime")
    parser.add_argument("input", help="Input image path")
    parser.add_argument("output", help="Output PNG path")
    args = parser.parse_args()

    project_root = Path(__file__).resolve().parents[1]
    rembg_binary = project_root / ".venv-rembg" / "bin" / "rembg"

    if not rembg_binary.is_file():
        print("ERROR: missing .venv-rembg/bin/rembg", file=sys.stderr)
        return 1

    result = subprocess.run(
        [str(rembg_binary), "i", args.input, args.output],
        capture_output=True,
        text=True,
    )

    if result.returncode != 0:
        if result.stdout:
            print(result.stdout.strip(), file=sys.stderr)
        if result.stderr:
            print(result.stderr.strip(), file=sys.stderr)
        return result.returncode

    if result.stdout:
        print(result.stdout.strip())

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
