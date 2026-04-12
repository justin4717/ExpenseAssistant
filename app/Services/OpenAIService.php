<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected $client;
    protected $apiKey;
    protected $model = 'gpt-3.5-turbo';

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => 30,
            'verify' => false, // Disable SSL verification for local development
        ]);
        $this->apiKey = env('OPENAI_API_KEY');
    }

    /**
     * Categorize a transaction description using OpenAI
     */
    public function categorizeTransaction($description, $amount, $availableCategories)
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            // Build category list for prompt
            $categoryList = collect($availableCategories)
                ->map(fn($cat) => "{$cat['name']} ({$cat['type']})")
                ->join(', ');

            $prompt = "You are a financial assistant. Categorize this transaction into one of the available categories.\n\n";
            $prompt .= "Transaction: \"{$description}\"\n";
            $prompt .= "Amount: \${$amount}\n\n";
            $prompt .= "Available categories: {$categoryList}\n\n";
            $prompt .= "Respond with ONLY the exact category name, nothing else.";

            $response = $this->client->post('chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a financial categorization expert. Respond with only the category name.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 50,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $categoryName = trim($data['choices'][0]['message']['content'] ?? '');

            // Find matching category
            $category = collect($availableCategories)->first(function ($cat) use ($categoryName) {
                return stripos($cat['name'], $categoryName) !== false || 
                       stripos($categoryName, $cat['name']) !== false;
            });

            return $category['id'] ?? null;

        } catch (\Exception $e) {
            Log::error('OpenAI categorization failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Batch categorize multiple transactions
     */
    public function batchCategorizeTransactions($transactions, $availableCategories)
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
                    'model' => $this->model,
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
            
            // Parse JSON response
            $categoryNames = json_decode($content, true);
            
            if (!is_array($categoryNames)) {
                return [];
            }

            // Map category names to IDs
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

    /**
     * Analyze PDF bank statement using GPT-4 Vision to extract and categorize transactions
     */
    public function analyzePDFStatement($base64Pdf, $availableCategories)
    {
        if (empty($this->apiKey)) {
            throw new \Exception('OpenAI API key is not configured. Please add OPENAI_API_KEY to your .env file.');
        }

        try {
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

        } catch (\Exception $e) {
            Log::error('OpenAI PDF analysis failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
