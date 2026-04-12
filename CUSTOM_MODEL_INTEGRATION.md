# Your Custom AI Model API

This is a template for your custom AI model that will integrate with the expense tracking app.

## API Contract

Your model needs to implement these two endpoints:

### 1. POST /analyze-pdf
Analyzes a PDF bank statement and extracts transactions.

**Request:**
```json
{
  "pdf": "base64_encoded_pdf_string",
  "categories": [
    {"id": 1, "name": "Groceries", "type": "expense", "icon": "🛒"},
    {"id": 2, "name": "Salary", "type": "income", "icon": "💰"}
  ]
}
```

**Response:**
```json
{
  "success": true,
  "transactions": [
    {
      "date": "2025-12-01",
      "description": "Walmart Purchase",
      "amount": "45.50",
      "category": "Groceries",
      "category_id": 1
    }
  ]
}
```

### 2. POST /categorize-batch
Categorizes multiple transactions.

**Request:**
```json
{
  "transactions": [
    {"description": "Starbucks Coffee", "amount": 5.50},
    {"description": "Shell Gas Station", "amount": 45.00}
  ],
  "categories": [...]
}
```

**Response:**
```json
{
  "success": true,
  "category_ids": [3, 8]
}
```

## Example Implementation (Python Flask)

```python
from flask import Flask, request, jsonify
from flask_cors import CORS
import base64
import json

# Import your model
# from your_model import PDFAnalyzer, TransactionCategorizer

app = Flask(__name__)
CORS(app)  # Allow requests from Laravel

# Initialize your model
# pdf_analyzer = PDFAnalyzer()
# categorizer = TransactionCategorizer()

@app.route('/analyze-pdf', methods=['POST'])
def analyze_pdf():
    try:
        data = request.json
        pdf_base64 = data['pdf']
        categories = data['categories']
        
        # Decode PDF
        pdf_bytes = base64.b64decode(pdf_base64)
        
        # YOUR MODEL LOGIC HERE
        # 1. Extract text/images from PDF
        # 2. Identify transaction patterns
        # 3. Parse dates, descriptions, amounts
        # 4. Categorize each transaction
        
        # Example output (replace with your model's actual output)
        transactions = [
            {
                "date": "2025-12-01",
                "description": "Example Transaction",
                "amount": "25.00",
                "category": "Groceries",
                "category_id": find_category_id("Groceries", categories)
            }
        ]
        
        return jsonify({
            'success': True,
            'transactions': transactions
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'message': str(e)
        }), 500

@app.route('/categorize-batch', methods=['POST'])
def categorize_batch():
    try:
        data = request.json
        transactions = data['transactions']
        categories = data['categories']
        
        # YOUR CATEGORIZATION MODEL HERE
        # For each transaction, predict the best category
        
        category_ids = []
        for txn in transactions:
            # predicted_category = categorizer.predict(txn['description'], txn['amount'])
            # category_id = find_category_id(predicted_category, categories)
            category_id = 1  # Replace with actual prediction
            category_ids.append(category_id)
        
        return jsonify({
            'success': True,
            'category_ids': category_ids
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'message': str(e)
        }), 500

def find_category_id(category_name, categories):
    """Helper to find category ID by name"""
    for cat in categories:
        if cat['name'].lower() == category_name.lower():
            return cat['id']
    return None

@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'healthy', 'model': 'your-model-v1'})

if __name__ == '__main__':
    print("🚀 Starting Custom AI Model API on http://localhost:5000")
    app.run(host='0.0.0.0', port=5000, debug=True)
```

## How to Integrate Your Model

### Step 1: Train Your Model
- Collect training data (bank statements + labeled transactions)
- Train your model (PyTorch, TensorFlow, Scikit-learn, etc.)
- Save the trained model weights

### Step 2: Create the API
Use the template above or create your own in any language:
- Python (Flask/FastAPI)
- Node.js (Express)
- Go
- Java/Spring

### Step 3: Run Your Model API
```bash
python your_model_api.py
```

### Step 4: Configure Laravel
Set in `.env`:
```env
AI_PROVIDER=custom
CUSTOM_AI_URL=http://localhost:5000/
```

### Step 5: Test
Upload a PDF in your expense app - it will now use YOUR model! 🎉

## Model Requirements

Your model should handle:

### PDF Analysis:
- ✅ Text extraction from PDFs
- ✅ Date parsing (various formats)
- ✅ Amount extraction (with currency symbols)
- ✅ Description cleaning
- ✅ Multi-page PDFs

### Categorization:
- ✅ Context understanding (not just keywords)
- ✅ Amount consideration (helps accuracy)
- ✅ Multiple transaction types
- ✅ Custom user categories

## Testing Your Integration

```bash
# Test PDF analysis
curl -X POST http://localhost:5000/analyze-pdf \
  -H "Content-Type: application/json" \
  -d '{"pdf":"JVBERi0x...", "categories":[...]}'

# Test categorization
curl -X POST http://localhost:5000/categorize-batch \
  -H "Content-Type: application/json" \
  -d '{"transactions":[...], "categories":[...]}'
```

## Deployment Options

### Development:
- Run locally on http://localhost:5000

### Production:
- Docker container
- Cloud VM (AWS EC2, Google Cloud, Azure)
- Serverless (AWS Lambda, Google Cloud Functions)
- Dedicated ML server with GPU

## Performance Tips

1. **Cache models** in memory (don't reload per request)
2. **Use batch processing** for multiple transactions
3. **Optimize PDF parsing** (use efficient libraries)
4. **Add request queuing** for high load
5. **Consider GPU** for deep learning models

## Next Steps

1. Implement the two API endpoints above
2. Test with `curl` or Postman
3. Set `AI_PROVIDER=custom` in `.env`
4. Upload a PDF in your expense app
5. See your model in action! 🚀

Your Laravel app is now ready to work with YOUR custom AI model!
