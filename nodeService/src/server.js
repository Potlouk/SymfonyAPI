const express  = require("express");
var bodyParser = require('body-parser');
const puppeteer = require("puppeteer");
const { template } = require("./template");

const app = express();
app.use(bodyParser.json({ limit: '200mb' }));
app.use(bodyParser.urlencoded({ extended: true, limit: '200mb' }));

app.post("/generate-pdf", async (req, res) => {
    const htmlContent = template(req.body.html);

    const browser = await puppeteer.launch({
        headless: "new",
        executablePath: `/usr/bin/google-chrome`,
        args: [`--no-sandbox`, `--headless`, `--disable-gpu`, `--disable-dev-shm-usage`],
    });

    const page = await browser.newPage();
    await page.setContent(htmlContent, {
        waitUntil: "networkidle0",
    });

    const pdfBuffer = await page.pdf({
        printBackground: true,
        format: "Letter",
    });

    await browser.close();

    res.setHeader("Content-Type", "application/pdf");
    res.setHeader("Content-Disposition", "attachment; filename=download.pdf");
    res.send(pdfBuffer);
});

app.listen(3000, () => {
    console.log("App is listening on port 3000!");
});