#!/usr/bin/env node
"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const inquirer_1 = __importDefault(require("inquirer"));
const fs = __importStar(require("fs"));
const path = __importStar(require("path"));
const fsPromises = __importStar(require("fs/promises"));
const child_process_1 = require("child_process");
const templatesDir = path.join(__dirname, "templates");
async function replaceInFiles(folderPath, searchValue, replaceValue) {
    const entries = await fsPromises.readdir(folderPath, {
        withFileTypes: true,
    });
    for (const entry of entries) {
        const entryPath = path.join(folderPath, entry.name);
        if (entry.isDirectory()) {
            await replaceInFiles(entryPath, searchValue, replaceValue);
        }
        else {
            const fileContent = await fsPromises.readFile(entryPath, "utf8");
            const updatedContent = fileContent.replace(new RegExp(searchValue, "g"), replaceValue);
            await fsPromises.writeFile(entryPath, updatedContent, "utf8");
        }
    }
}
const main = async () => {
    const { projectType } = await inquirer_1.default.prompt([
        {
            type: "list",
            name: "projectType",
            message: "Which project do you want to expand?",
            choices: ["WordPress"],
        },
    ]);
    const projectMapping = {
        WordPress: "wordpress",
    };
    const selectedTemplate = projectMapping[projectType];
    const templatePath = path.join(templatesDir, selectedTemplate);
    if (!fs.existsSync(templatePath)) {
        console.error("Template not found!");
        process.exit(1);
    }
    if (selectedTemplate === projectMapping.WordPress) {
        const { themeName } = await inquirer_1.default.prompt([
            {
                type: "input",
                name: "themeName",
                message: "Enter a name for your WordPress theme:",
                validate: (input) => {
                    if (input.trim().length === 0) {
                        return "Theme name cannot be empty.";
                    }
                    if (input.includes(" ") ||
                        input.includes("-") ||
                        input.includes("_")) {
                        return "Theme name must be camelCase.";
                    }
                    return true;
                },
            },
        ]);
        const destinationPath = process.cwd();
        console.log(`Copying template from '${templatePath}' to '${destinationPath}'`);
        (0, child_process_1.execSync)(`cp -r ${templatePath}/* ${destinationPath}`);
        const themeFolderPath = path.join(destinationPath, "wp-content", "themes", "blank");
        const newThemeFolderPath = path.join(destinationPath, "wp-content", "themes", themeName);
        if (!fs.existsSync(themeFolderPath)) {
            console.error(`The expected theme folder 'blank' does not exist in the copied template.`);
            process.exit(1);
        }
        await fsPromises.rename(themeFolderPath, newThemeFolderPath);
        console.log(`Customizing files in: ${destinationPath}`);
        await replaceInFiles(destinationPath, "blank", themeName);
        console.log(`Project expanded successfully with theme '${themeName}'.`);
        console.log(`Next don't forget: \n
            1. Change database connection to real db.\n
            2. Install all necessary plugins. (wp-content/initial-plugins) \n
            `);
    }
};
main().catch((error) => {
    console.error("Something went wrong:", error.message);
    process.exit(1);
});
