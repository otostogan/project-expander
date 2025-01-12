#!/usr/bin/env node

import inquirer from "inquirer";
import * as fs from "fs";
import * as path from "path";
import * as fsPromises from "fs/promises";
import { execSync } from "child_process";

interface ProjectMapping {
    [key: string]: string;
}

const templatesDir = path.join(__dirname, "templates");

async function replaceInFiles(
    folderPath: string,
    searchValue: string,
    replaceValue: string,
) {
    const entries = await fsPromises.readdir(folderPath, {
        withFileTypes: true,
    });

    for (const entry of entries) {
        const entryPath = path.join(folderPath, entry.name);

        if (entry.isDirectory()) {
            await replaceInFiles(entryPath, searchValue, replaceValue);
        } else {
            const fileContent = await fsPromises.readFile(entryPath, "utf8");
            const updatedContent = fileContent.replace(
                new RegExp(searchValue, "g"),
                replaceValue,
            );
            await fsPromises.writeFile(entryPath, updatedContent, "utf8");
        }
    }
}

const main = async () => {
    const { projectType } = await inquirer.prompt([
        {
            type: "list",
            name: "projectType",
            message: "Which project do you want to expand?",
            choices: ["WordPress"],
        },
    ]);

    const projectMapping: ProjectMapping = {
        WordPress: "wordpress",
    };

    const selectedTemplate = projectMapping[projectType];
    const templatePath = path.join(templatesDir, selectedTemplate);

    if (!fs.existsSync(templatePath)) {
        console.error("Template not found!");
        process.exit(1);
    }

    if (selectedTemplate === projectMapping.WordPress) {
        const { themeName } = await inquirer.prompt([
            {
                type: "input",
                name: "themeName",
                message: "Enter a name for your WordPress theme:",
                validate: (input) => {
                    if (input.trim().length === 0) {
                        return "Theme name cannot be empty.";
                    }
                    if (
                        input.includes(" ") ||
                        input.includes("-") ||
                        input.includes("_")
                    ) {
                        return "Theme name must be camelCase.";
                    }
                    return true;
                },
            },
        ]);

        const destinationPath = process.cwd();

        console.log(
            `Copying template from '${templatePath}' to '${destinationPath}'`,
        );
        execSync(`cp -r ${templatePath}/* ${destinationPath}`);

        const themeFolderPath = path.join(
            destinationPath,
            "wp-content",
            "themes",
            "blank",
        );
        const newThemeFolderPath = path.join(
            destinationPath,
            "wp-content",
            "themes",
            themeName,
        );

        if (!fs.existsSync(themeFolderPath)) {
            console.error(
                `The expected theme folder 'blank' does not exist in the copied template.`,
            );
            process.exit(1);
        }

        await fsPromises.rename(themeFolderPath, newThemeFolderPath);

        console.log(`Customizing files in: ${destinationPath}`);
        await replaceInFiles(destinationPath, "blank", themeName);

        console.log(`Project expanded successfully with theme '${themeName}'.`);
        console.log(
            `Next don't forget: \n
            1. Change database connection to real db.\n
            2. Install all necessary plugins. (wp-content/initial-plugins) \n
            `,
        );
    }
};

main().catch((error) => {
    console.error("Something went wrong:", error.message);
    process.exit(1);
});
