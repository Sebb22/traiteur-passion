import fs from "node:fs";
import path from "node:path";

const ROOT = process.cwd();
const JS_DIR = path.join(ROOT, "resources", "js");
const BROKEN_PATTERN = /\?\s+\./g;

function walk(dir, out = []) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });

    for (const entry of entries) {
        const fullPath = path.join(dir, entry.name);

        if (entry.isDirectory()) {
            walk(fullPath, out);
            continue;
        }

        if (entry.isFile() && fullPath.endsWith(".js")) {
            out.push(fullPath);
        }
    }

    return out;
}

if (!fs.existsSync(JS_DIR)) {
    console.log("No resources/js directory found. Skipping fix.");
    process.exit(0);
}

const files = walk(JS_DIR);
let changedCount = 0;

for (const file of files) {
    const original = fs.readFileSync(file, "utf8");
    const fixed = original.replace(BROKEN_PATTERN, "?.");

    if (fixed !== original) {
        fs.writeFileSync(file, fixed, "utf8");
        changedCount += 1;
        console.log(`fixed: ${path.relative(ROOT, file)}`);
    }
}

if (changedCount === 0) {
    console.log("No broken optional chaining found.");
} else {
    console.log(`Fixed optional chaining in ${changedCount} file(s).`);
}