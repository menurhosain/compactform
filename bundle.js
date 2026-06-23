const fs = require("fs");
const path = require("path");
const fse = require("fs-extra");
const archiver = require("archiver");

const BUILD_NAME = "compactform";
const MAIN_FILE = path.join(".", "compactform.php");
const DIST_DIR = path.join(".", "dist");
const BUILD_DIR = path.join(".", BUILD_NAME);

function getVersion() {
  const header = fs.readFileSync(MAIN_FILE, "utf8");
  const versionMatch = header.match(/Version:\s*([\d.]+)/);

  if (!versionMatch) {
    throw new Error("Could not find plugin Version header in " + MAIN_FILE);
  }

  return versionMatch[1];
}

const EXCLUDE = [
  BUILD_NAME,
  `${BUILD_NAME}.zip`,
  "dist",
  "phpcs.xml.dist",
  "node_modules",
  ".claude",
  ".agents",
  "claude.md",
  ".git",
  ".gitignore",
  "Thumbs.db",
  "package.json",
  "package-lock.json",
  "composer.json",
  "composer.lock",
  "bundle.js",
  "gulpfile.js",
  "src",
  "notes.md",
  ".php-cs-fixer.dist.php",
  ".php-cs-fixer.cache",
  "skills-lock.json",
  "thinking.md",
  // Composer's own (unprefixed) vendor dir never ships — only the
  // Strauss-prefixed vendor_prefix/ is safe to load alongside other plugins.
  "vendor",
  "bin",
];

// Matched by basename alone, at any depth (not just repo root).
const EXCLUDE_ANYWHERE = [".php-cs-fixer.dist.php", ".php-cs-fixer.cache"];

function shouldExclude(filePath) {
  const normalized = filePath.replace(/\\/g, "/").replace(/^\.\//, "");
  if (normalized === "node_modules" || normalized.includes("/node_modules")) {
    return true;
  }
  if (EXCLUDE_ANYWHERE.includes(path.basename(normalized))) {
    return true;
  }
  return EXCLUDE.some(
    (excl) => normalized === excl || normalized.startsWith(excl + "/"),
  );
}

function formatSize(bytes) {
  if (bytes >= 1024 * 1024) {
    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
  }
  return `${(bytes / 1024).toFixed(2)} KB`;
}

// 1. COPY
fse.removeSync(BUILD_DIR);
fs.mkdirSync(BUILD_DIR, { recursive: true });

let copiedFiles = 0;

function copyRecursive(srcDir, dstDir, base = "") {
  const items = fs.readdirSync(srcDir);
  items.forEach((item) => {
    const relativePath = path.join(base, item).replace(/\\/g, "/");
    if (shouldExclude(relativePath)) {
      return;
    }

    const srcItem = path.join(srcDir, item);
    const dstItem = path.join(dstDir, item);
    const stat = fs.statSync(srcItem);

    if (stat.isDirectory()) {
      fs.mkdirSync(dstItem, { recursive: true });
      copyRecursive(srcItem, dstItem, relativePath);
    } else {
      fse.copySync(srcItem, dstItem);
      copiedFiles++;
      process.stdout.write(`\r  Copying... ${copiedFiles} files`);
    }
  });
}

process.stdout.write("  Copying...");
copyRecursive(".", BUILD_DIR);
console.log(`\r  Copied ${copiedFiles} files\n`);

// 2. ZIP
const version = getVersion();
fse.ensureDirSync(DIST_DIR);
const zipPath = path.join(DIST_DIR, `${BUILD_NAME}-${version}.zip`);
const output = fs.createWriteStream(zipPath);
const archive = archiver("zip", { zlib: { level: 9 } });

let zippedFiles = 0;

archive.on("entry", () => {
  zippedFiles++;
  process.stdout.write(`\r  Zipping... ${zippedFiles} files`);
});

archive.on("error", (err) => {
  throw err;
});

output.on("close", () => {
  fse.removeSync(BUILD_DIR);
  const size = formatSize(archive.pointer());
  console.log(`\r  Zipped ${zippedFiles} files (${size})`);
  console.log(`\n  BUILD COMPLETE -> ${zipPath}\n`);
});

process.stdout.write("\n  Zipping...");
archive.pipe(output);
archive.directory(BUILD_DIR, BUILD_NAME);
archive.finalize();
