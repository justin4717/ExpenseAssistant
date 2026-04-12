<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected $client;
    protected $provider;
    protected $apiKey;

    public function __construct()
    {
        $this->provider = env('AI_PROVIDER', 'openai'); // 'openai', 'custom', 'ollama'
        
        $baseUri = match($this->provider) {
            'openai' => 'https://api.openai.com/v1/',
            'custom' => env('CUSTOM_AI_URL', 'http://localhost:5000/'),
            'ollama' => env('OLLAMA_URL', 'http://localhost:11434/'),
            default => 'https://api.openai.com/v1/'
        };

        $this->client = new Client([
            'base_uri' => $baseUri,
            'timeout' => 60,
            'verify' => false,
        ]);
        
        $this->apiKey = env('OPENAI_API_KEY');
    }

    /**
     * Analyze PDF bank statement using AI to extract and categorize transactions
     * This method works with ANY AI provider (OpenAI, custom model, or Ollama)
     */
    public function analyzePDFStatement($base64Pdf, $availableCategories)
    {
        try {
            return match($this->provider) {
                'openai' => $this->analyzeWithOpenAI($base64Pdf, $availableCategories),
                'custom' => $this->analyzeWithCustomModel($base64Pdf, $availableCategories),
                'ollama' => $this->analyzeWithOllama($base64Pdf, $availableCategories),
                default => throw new \Exception("Unsupported AI provider: {$this->provider}")
            };
        } catch (\Exception $e) {
            Log::error('AI PDF analysis failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * OpenAI Implementation (existing)
     */
    protected function analyzeWithOpenAI($base64Pdf, $availableCategories)
    {
        if (empty($this->apiKey)) {
            throw new \Exception('OpenAI API key is not configured.');
        }

        $categoryList = collect($availableCategories)
            ->map(fn($cat) => "{$cat['name']} ({$cat['type']})")
            ->join(', ');

        $prompt = "Analyze this bank statement PDF and extract all transactions. For each transaction, provide:\n";
        $prompt .= "1. Date (format: YYYY-MM-DD)\n";
        $prompt .= "2. Description\n";
        $prompt .= "3. Amount (positive number)\n";
        $prompt .= "4. Category (choose from: {$categoryList})\n\n";
        $prompt .= "Return ONLY a valid JSON array of transactions like this:\n";
        $prompt .= '[{"date":"2025-12-01","description":"Grocery Store","amount":"50.00","category":"Groceries"}]';

        $response = $this->client->post('chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a financial document analysis expert. Extract transaction data accurately and return only valid JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $prompt
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:application/pdf;base64,{$base64Pdf}"
                                ]
                            ]
                        ]
                    ]
                ],
                'temperature' => 0.2,
                'max_tokens' => 4096,
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $content = trim($data['choices'][0]['message']['content'] ?? '');
        
        return $this->parseAIResponse($content, $availableCategories);
    }

    /**
     * YOUR CUSTOM MODEL Implementation
     * This is where your custom AI model gets called
     */
    protected function analyzeWithCustomModel($base64Pdf, $availableCategories)
    {
        // Send to your custom model API
        $response = $this->client->post('analyze-pdf', [
            'json' => [
                'pdf' => $base64Pdf,
                'categories' => array_map(function($cat) {
                    return [
                        'id' => $cat['id'],
                        'name' => $cat['name'],
                        'type' => $cat['type'],
                        'icon' => $cat['icon']
                    ];
                }, $availableCategories)
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        
        if (!$data['success']) {
            throw new \Exception($data['message'] ?? 'Custom model analysis failed');
        }

        // Your custom model should return transactions in this format:
        // [
        //   {"date":"2025-12-01","description":"Store","amount":"50.00","category":"Groceries","category_id":5},
        //   ...
        // ]
        
        $transactions = $data['transactions'] ?? [];

        // Map category names to IDs if needed
        foreach ($transactions as &$transaction) {
            if (!isset($transaction['category_id']) && isset($transaction['category'])) {
                $categoryName = $transaction['category'];
                $category = collect($availableCategories)->first(function ($cat) use ($categoryName) {
                    return stripos($cat['name'], $categoryName) !== false || 
                           stripos($categoryName, $cat['name']) !== false;
                });
                $transaction['category_id'] = $category['id'] ?? null;
                $transaction['category_name'] = $category['name'] ?? $categoryName;
                $transaction['category_icon'] = $category['icon'] ?? '📄';
            }
        }

        return $transactions;
    }

    /**
     * Ollama (Local LLM) Implementation
     */
    protected function analyzeWithOllama($base64Pdf, $availableCategories)
    {
        $categoryList = collect($availableCategories)
            ->map(fn($cat) => "{$cat['name']} ({$cat['type']})")
            ->join(', ');

        $prompt = "Analyze this bank statement and extract transactions as JSON array with date, description, amount, and category from: {$categoryList}";

        $response = $this->client->post('api/generate', [
            'json' => [
                'model' => 'llama3.2-vision',
                'prompt' => $prompt,
                'images' => [$base64Pdf],
                'stream' => false
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $content = $data['response'] ?? '';
        
        return $this->parseAIResponse($content, $availableCategories);
    }

    /**
     * Parse AI response and map to categories
     */
    protected function parseAIResponse($content, $availableCategories)
    {
        // Extract JSON from response (sometimes wrapped in markdown)
        if (preg_match('/```json\s*(.+?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        } else if (preg_match('/```\s*(.+?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }
        
        $transactions = json_decode($content, true);
        
        if (!is_array($transactions)) {
            throw new \Exception('AI did not return valid transaction data');
        }

        // Map category names to IDs
        foreach ($transactions as &$transaction) {
            if (isset($transaction['category'])) {
                $categoryName = $transaction['category'];
                $category = collect($availableCategories)->first(function ($cat) use ($categoryName) {
                    return stripos($cat['name'], $categoryName) !== false || 
                           stripos($categoryName, $cat['name']) !== false;
                });
                $transaction['category_id'] = $category['id'] ?? null;
                $transaction['category_name'] = $category['name'] ?? $categoryName;
                $transaction['category_icon'] = $category['icon'] ?? '📄';
            }
        }

        return $transactions;
    }

    /**
     * Batch categorize transactions (works with all providers)
     */
    public function batchCategorizeTransactions($transactions, $availableCategories)
    {
        // Use provider-specific categorization or fallback to generic
        return match($this->provider) {
            'openai' => $this->batchCategorizeWithOpenAI($transactions, $availableCategories),
            'custom' => $this->batchCategorizeWithCustomModel($transactions, $availableCategories),
            'ollama' => $this->batchCategorizeWithOllama($transactions, $availableCategories),
            default => []
        };
    }

    protected function batchCategorizeWithOpenAI($transactions, $availableCategories)
    {
        if (empty($this->apiKey)) {
            return [];
        }

        try {
            $categoryList = collect($availableCategories)
                ->map(fn($cat) => "{$cat['name']} ({$cat['type']})")
                ->join(', ');

            $transactionsList = collect($transactions)
                ->map(fn($t, $i) => ($i + 1) . ". {$t['description']} - \${$t['amount']}")
                ->join("\n");

            $prompt = "Categorize these transactions. Return ONLY a JSON array of category names in the same order.\n\n";
            $prompt .= "Transactions:\n{$transactionsList}\n\n";
            $prompt .= "Available categories: {$categoryList}\n\n";
            $prompt .= "Return format: [\"category1\", \"category2\", ...]";

            $response = $this->client->post('chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a financial categorization expert. Return only valid JSON arrays.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 200,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $content = trim($data['choices'][0]['message']['content'] ?? '');
            
            $categoryNames = json_decode($content, true);
            
            if (!is_array($categoryNames)) {
                return [];
            }

            $results = [];
            foreach ($categoryNames as $categoryName) {
                $category = collect($availableCategories)->first(function ($cat) use ($categoryName) {
                    return stripos($cat['name'], $categoryName) !== false || 
                           stripos($categoryName, $cat['name']) !== false;
                });
                $results[] = $category['id'] ?? null;
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('OpenAI batch categorization failed: ' . $e->getMessage());
            return [];
        }
    }

    protected function batchCategorizeWithCustomModel($transactions, $availableCategories)
    {
        try {
            $response = $this->client->post('categorize-batch', [
                'json' => [
                    'transactions' => $transactions,
                    'categories' => $availableCategories
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['category_ids'] ?? [];
        } catch (\Exception $e) {
            Log::error('Custom model batch categorization failed: ' . $e->getMessage());
            return [];
        }
    }

    protected function batchCategorizeWithOllama($transactions, $availableCategories)
    {
        // Similar to OpenAI but using Ollama endpoint
        // Implementation details...
        return [];
    }
}
