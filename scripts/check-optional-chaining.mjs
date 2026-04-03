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

function findBrokenOptionalChaining(filePath) {
    const content = fs.readFileSync(filePath, "utf8");
    const lines = content.split(/\r?\n/);
    const matches = [];

    for (let i = 0; i < lines.length; i += 1) {
        if (BROKEN_PATTERN.test(lines[i])) {
            matches.push({ line: i + 1, text: lines[i].trim() });
        }
        BROKEN_PATTERN.lastIndex = 0;
    }

    return matches;
}

if (!fs.existsSync(JS_DIR)) {
    console.log("✅ No resources/js directory found. Skipping optional chaining check.");
    process.exit(0);
}

const jsFiles = walk(JS_DIR);
const issues = [];

for (const file of jsFiles) {
    const broken = findBrokenOptionalChaining(file);
    if (!broken.length) continue;

    const rel = path.relative(ROOT, file);
    for (const issue of broken) {
        issues.push(`${rel}:${issue.line}  ${issue.text}`);
    }
}

if (issues.length) {
    console.error("\n❌ Broken optional chaining detected (`? .` instead of `?.`)\n");
    for (const issue of issues) {
        console.error(`- ${issue}`);
    }
    console.error("\nFix these lines before building.\n");
    process.exit(1);
}

console.log("✅ Optional chaining check passed.");
