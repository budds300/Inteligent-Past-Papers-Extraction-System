# Intelligent Past Papers Extraction System

A Laravel-based web application for uploading, extracting, and managing exam papers using AI (OpenAI & DeepSeek).

---

## Features

- **Upload Exam Papers:** Supports PDF, DOCX, ZIP, and image files (JPG, PNG).
- **AI Extraction:** Automatically extracts questions, answers, and metadata using OpenAI and DeepSeek APIs.
- **Background Processing:** Uses Laravel Jobs and Queues for scalable, asynchronous file processing.
- **Question Paper Management:** Browse, search, and view extracted question papers and their content.
- **API Support:** REST endpoints for uploading and fetching question paper data.

---

## How It Works

1. **Upload:**  
   Users upload a file (PDF, DOCX, ZIP, or image) via web or API.

2. **Storage:**  
   The file is saved, and a `QuestionPaper` record is created.

3. **Processing:**  
   A background job (`ProcessQuestionPaperJob`) is dispatched to process the upload.

4. **Extraction:**  
   - ZIP files are extracted; each file is processed individually.
   - PDFs/DOCX are parsed for text.
   - Images are analyzed using AI.

5. **AI Extraction:**  
   Extracted text is sent to OpenAI and/or DeepSeek for question, answer, and metadata extraction.

6. **Data Storage:**  
   Extracted questions, answers, images, and metadata are saved and linked to the `QuestionPaper`.

7. **Review:**  
   Users can view and manage extracted content via the web interface.

---

## Project Structure

- `app/Http/Controllers/` — Handles HTTP requests (file uploads, paper viewing).
- `app/Services/` — File and AI processing logic (`FileProcessorService`, `OpenAiService`, `DeepSeekService`).
- `app/Jobs/` — Background jobs for processing uploads (`ProcessQuestionPaperJob`, `ProcessDocumentJob`).
- `resources/views/` — Blade templates for the web UI.
- `routes/web.php` & `routes/api.php` — Web and API routes.

---

## Environment Variables

Add these to your `.env` file:

```env
DEEPSEEK_API_KEY=your_deepseek_api_key
OPENAI_API_KEY=your_openai_api_key

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=exam_extraction
DB_USERNAME=root
DB_PASSWORD=

---

## Setup & Usage

### 1. Install Dependencies

Install PHP dependencies via Composer and JavaScript dependencies via npm:

```bash
composer install
npm install && npm run dev