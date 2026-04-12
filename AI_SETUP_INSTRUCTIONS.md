# AI-Powered Transaction Categorization Setup

## 🤖 OpenAI Integration

Your Expense Assistant now supports AI-powered transaction categorization using OpenAI's GPT models!

## Setup Instructions

### 1. Get OpenAI API Key

1. Go to [platform.openai.com](https://platform.openai.com)
2. Create an account or sign in
3. Navigate to API Keys section
4. Click "Create new secret key"
5. Copy the API key (starts with `sk-...`)

### 2. Add API Key to Your Application

Open `.env` file in your project root and add:

```env
OPENAI_API_KEY=sk-your-actual-api-key-here
```

### 3. Restart Laravel Server

After adding the API key, restart your Laravel development server:

```bash
php artisan config:clear
php artisan cache:clear
```

## Usage

1. Go to **Transactions** page
2. Click **Import CSV** or **Import PDF**
3. Upload your bank statement
4. Toggle **"Use AI-Powered Categorization"** checkbox
5. The AI will automatically categorize all transactions with high accuracy!

## Pricing

- **GPT-3.5-Turbo**: ~$0.002 per 1,000 tokens
- **Average cost**: ~$0.50 per 1,000 transactions
- **Free tier**: $5 credit for new accounts
- Very affordable for personal use!

## Features

✅ **Smart categorization** - Understands merchant names and context
✅ **Batch processing** - Efficient API usage
✅ **Automatic fallback** - Uses keyword matching if API fails
✅ **Works with CSV and PDF** - Both formats supported
✅ **High accuracy** - Much better than keyword matching

## How It Works

1. **CSV/PDF Upload**: System extracts transaction data
2. **AI Analysis**: Sends descriptions to GPT for categorization
3. **Category Matching**: Maps AI responses to your categories
4. **Import**: Creates transactions with detected categories

## Troubleshooting

**AI categorization not working?**
- Check your API key is correctly set in `.env`
- Ensure you have API credits in your OpenAI account
- Check `storage/logs/laravel.log` for error messages
- AI will automatically fall back to keyword matching if it fails

**Want to disable AI?**
- Simply uncheck "Use AI-Powered Categorization" in import modal
- System will use the original keyword-based detection

## Security Note

⚠️ **Never commit your `.env` file to version control!**
Your API key should remain private. The `.env` file is already in `.gitignore`.

---

Enjoy smarter expense tracking! 🎉
