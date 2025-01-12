import fs = require("fs");
import path = require("path");

const srcPath = path.join(__dirname, "../src/templates");
const destPath = path.join(__dirname, "" + "templates");

const copyFolderSync = (src: string, dest: string) => {
    if (!fs.existsSync(dest)) {
        fs.mkdirSync(dest, { recursive: true });
    }
    fs.readdirSync(src).forEach((item) => {
        const srcItem = path.join(src, item);
        const destItem = path.join(dest, item);
        if (fs.lstatSync(srcItem).isDirectory()) {
            copyFolderSync(srcItem, destItem);
        } else {
            fs.copyFileSync(srcItem, destItem);
        }
    });
};

copyFolderSync(srcPath, destPath);
console.log("Templates folder copied successfully!");
