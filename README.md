# Intelligent Past Papers Extraction System

## Overview

The Intelligent Past Papers Extraction System is a Laravel-based application that leverages AI (OpenAI) to extract structured data from exam papers. This system processes various document formats and organizes the content into a searchable database.

## Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL or another supported database
- Web server (Apache, Nginx, etc.)
- Valid API keys for OpenAI

# 🧠 Intelligent Past Papers Extraction System

## 📄 Overview

The Intelligent Past Papers Extraction System is a Laravel-based application that leverages AI (OpenAI) to extract structured data from exam papers. This system processes various document formats and organizes the content into a searchable database.

---

## 🛠️ Prerequisites

- PHP 8.2 or higher  
- Composer  
- MySQL or another supported database  
- Web server (Apache, Nginx, etc.)  
- Valid API keys for OpenAI  

---

## ⚙️ Installation

```bash
# Clone the repository
git clone https://github.com/budds300/Inteligent-Past-Papers-Extraction-System.git
cd Inteligent-Past-Papers-Extraction-System

# Install dependencies
composer install

# Copy environment file and configure
cp .env.example .env
# Edit .env with your database credentials and API keys

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Create storage link
php artisan storage:link

# Start the development server
php artisan serve

## Configuration

Edit your `.env` file to include the necessary API keys:

```env
DEEPSEEK_API_KEY=your_deepseek_api_key
OPENAI_API_KEY=your_openai_api_key

# 🚀 Advanced Features

## 📡 API Access

The system provides API endpoints for programmatic access:

---

### 📤 Upload API

**Endpoint:**  
`POST /api/question-papers/upload`

**Description:**  
Send a file and metadata as `multipart/form-data`.

**Response:**  
Returns JSON with:
- Processing status
- Paper ID

---

### 📥 Status API

**Endpoint:**  
`GET /api/question-papers/{id}`

**Description:**  
Returns JSON with extraction results if processing is complete.

---

## ⚙️ Queue Management

For high-volume environments, configure queue workers:

```bash
php artisan queue:work
