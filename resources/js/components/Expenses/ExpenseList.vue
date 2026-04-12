<template>
  <AppLayout page-title="Transactions" page-icon="💸" page-subtitle="Track your income and expenses">
    <template #header-actions>
      <div class="header-actions">
        <button @click="showImportModal = true" class="btn-secondary">
          <span class="btn-icon">📄</span>
          Import CSV
        </button>
        <button @click="showCreateForm = true" class="btn-primary">
          <span class="btn-icon">➕</span>
          Add Transaction
        </button>
      </div>
    </template>

    <div class="transactions-content">
      <!-- Filters Row -->
      <div class="filters-row">
        <div class="filters-section">
          <div class="filter-group">
            <label>Type</label>
            <select v-model="filters.type" @change="applyFilters">
              <option value="">All Types</option>
              <option value="expense">Expenses</option>
              <option value="income">Income</option>
            </select>
          </div>

          <div class="filter-group">
            <label>Category</label>
            <select v-model="filters.category_id" @change="fetchExpenses">
              <option value="">All Categories</option>
              <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">
                {{ cat.icon }} {{ cat.name }}
              </option>
            </select>
          </div>

          <div class="filter-group">
            <label>Payment Method</label>
            <select v-model="filters.payment_method" @change="fetchExpenses">
              <option value="">All Methods</option>
              <option value="cash">Cash</option>
              <option value="card">Card</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="digital_wallet">Digital Wallet</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div class="filter-group">
            <label>From Date</label>
            <input v-model="filters.from_date" type="date" @change="fetchExpenses" />
          </div>

          <div class="filter-group">
            <label>To Date</label>
            <input v-model="filters.to_date" type="date" @change="fetchExpenses" />
          </div>

          <button @click="clearFilters" class="btn-secondary">Clear Filters</button>
        </div>
      </div>

      <!-- CSV Import Modal -->
      <div v-if="showImportModal" class="modal-overlay" @click="closeImportModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>📄 Import Transactions from CSV or PDF</h3>
            <button @click="closeImportModal" class="close-btn">✕</button>
          </div>
          
          <div class="modal-body">
            <div class="import-instructions">
              <h4>📋 Instructions:</h4>
              <ol>
                <li>Download your bank statement as CSV or PDF</li>
                <li><strong>CSV:</strong> Should have columns like Date, Description, Amount</li>
                <li><strong>PDF:</strong> AI will analyze and extract transactions automatically 🤖</li>
                <li>Upload the file below</li>
                <li>Map the columns (for CSV) or review AI-extracted data (for PDF)</li>
                <li>Review and confirm the import</li>
              </ol>
            </div>

            <div v-if="!csvFile" class="upload-zone">
              <input 
                type="file" 
                ref="fileInput" 
                accept=".csv,.pdf" 
                @change="handleFileSelect" 
                style="display: none;"
              />
              <div @click="$refs.fileInput.click()" class="upload-area">
                <div class="upload-icon">📤</div>
                <p class="upload-text">Click to upload CSV or PDF file</p>
                <p class="upload-hint">PDF files will be analyzed using AI</p>
              </div>
            </div>

            <div v-else class="csv-preview">
              <div class="file-info">
                <span class="file-name">📄 {{ csvFile.name }}</span>
                <button @click="removeFile" class="btn-text">Remove</button>
              </div>

              <div v-if="csvData.length > 0" class="column-mapping">
                <h4>Map CSV Columns:</h4>
                <div class="mapping-grid">
                  <div class="mapping-item">
                    <label>Date Column:</label>
                    <select v-model="columnMapping.date">
                      <option value="">-- Select Column --</option>
                      <option v-for="col in csvHeaders" :key="col" :value="col">{{ col }}</option>
                    </select>
                  </div>
                  <div class="mapping-item">
                    <label>Description Column:</label>
                    <select v-model="columnMapping.description">
                      <option value="">-- Select Column --</option>
                      <option v-for="col in csvHeaders" :key="col" :value="col">{{ col }}</option>
                    </select>
                  </div>
                  <div class="mapping-item">
                    <label>Amount Column:</label>
                    <select v-model="columnMapping.amount">
                      <option value="">-- Select Column --</option>
                      <option v-for="col in csvHeaders" :key="col" :value="col">{{ col }}</option>
                    </select>
                  </div>
                  <div class="mapping-item">
                    <label>Type Column (optional):</label>
                    <select v-model="columnMapping.type">
                      <option value="">-- Select Column --</option>
                      <option v-for="col in csvHeaders" :key="col" :value="col">{{ col }}</option>
                    </select>
                  </div>
                </div>

                <div class="default-settings">
                  <h4>Categorization Settings:</h4>
                  
                  <div class="ai-toggle">
                    <label class="toggle-label">
                      <input type="checkbox" v-model="useAICategorization" />
                      <span class="toggle-text">
                        🤖 <strong>Use AI-Powered Categorization</strong>
                        <span class="ai-badge">GPT</span>
                      </span>
                    </label>
                    <p class="ai-description" v-if="useAICategorization">
                      ✨ AI will intelligently categorize transactions with high accuracy. Requires OpenAI API key in settings.
                    </p>
                    <p class="ai-description" v-else>
                      📋 Using keyword-based auto-detection. Enable AI for better accuracy.
                    </p>
                  </div>

                  <div class="form-row">
                    <div class="form-group">
                      <label>Fallback Category (optional):</label>
                      <select v-model="importDefaults.category_id">
                        <option value="">-- Auto-detect only --</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                          {{ cat.icon }} {{ cat.name }}
                        </option>
                      </select>
                      <small class="help-text">Used when {{ useAICategorization ? 'AI' : 'keyword' }} detection fails</small>
                    </div>
                    <div class="form-group">
                      <label>Payment Method:</label>
                      <select v-model="importDefaults.payment_method">
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="card">Card</option>
                        <option value="cash">Cash</option>
                        <option value="digital_wallet">Digital Wallet</option>
                        <option value="other">Other</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="preview-section">
                  <h4>Preview (First 5 rows with detected categories):</h4>
                  <div class="preview-table">
                    <table>
                      <thead>
                        <tr>
                          <th>Date</th>
                          <th>Description</th>
                          <th>Amount</th>
                          <th>Category</th>
                          <th>Type</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(row, idx) in previewRows" :key="idx">
                          <td>{{ row.date }}</td>
                          <td>{{ row.description }}</td>
                          <td>${{ row.amount }}</td>
                          <td><span class="category-badge">{{ row.category }}</span></td>
                          <td>{{ row.type }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <p class="preview-note">Total rows: {{ csvData.length }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button @click="closeImportModal" class="btn-secondary">Cancel</button>
            <button 
              @click="importTransactions" 
              class="btn-primary" 
              :disabled="!canImport || importing"
            >
              {{ importing ? 'Importing...' : 'Import Transactions' }}
            </button>
          </div>

          <div v-if="importResult" class="import-result" :class="importResult.success ? 'success' : 'error'">
            <p>{{ importResult.message }}</p>
            <p v-if="importResult.imported">Imported: {{ importResult.imported }} transactions</p>
            <p v-if="importResult.duplicates">Skipped duplicates: {{ importResult.duplicates }}</p>
          </div>
        </div>
      </div>

      <!-- Create/Edit Form -->
      <div v-if="showCreateForm || editingExpense" class="form-card">
        <h3>{{ editingExpense ? 'Edit Transaction' : 'Add New Transaction' }}</h3>
        <form @submit.prevent="editingExpense ? updateExpense() : createExpense()">
          <div class="form-row">
            <div class="form-group">
              <label>Amount *</label>
              <input v-model.number="form.amount" type="number" step="0.01" required placeholder="25.50" />
            </div>

            <div class="form-group">
              <label>Date *</label>
              <input v-model="form.date" type="date" required />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Category *</label>
              <select v-model="form.category_id" required>
                <option value="">Select Category</option>
                <optgroup label="💸 Expenses">
                  <option v-for="cat in expenseCategories" :key="cat.id" :value="cat.id">
                    {{ cat.icon }} {{ cat.name }}
                  </option>
                </optgroup>
                <optgroup label="💰 Income">
                  <option v-for="cat in incomeCategories" :key="cat.id" :value="cat.id">
                    {{ cat.icon }} {{ cat.name }}
                  </option>
                </optgroup>
              </select>
            </div>

            <div class="form-group">
              <label>Payment Method *</label>
              <select v-model="form.payment_method" required>
                <option value="">Select Method</option>
                <option value="cash">💵 Cash</option>
                <option value="card">💳 Card</option>
                <option value="bank_transfer">🏦 Bank Transfer</option>
                <option value="digital_wallet">📱 Digital Wallet</option>
                <option value="other">📝 Other</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Notes</label>
            <textarea v-model="form.notes" rows="3" placeholder="Add details about this expense..."></textarea>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-primary">
              {{ editingExpense ? 'Update' : 'Create' }}
            </button>
            <button type="button" @click="cancelForm" class="btn-secondary">
              Cancel
            </button>
          </div>
        </form>
      </div>

      <div v-if="loading" class="loading">Loading transactions...</div>

      <div v-else-if="expenses.length === 0" class="empty-state">
        <p>💸 No transactions yet</p>
        <p>Start tracking your income and expenses</p>
      </div>

      <div v-else class="expenses-container">
        <!-- Summary Cards -->
        <div class="summary-cards">
          <div class="summary-card income-card">
            <h4>Total Income</h4>
            <p class="amount">+${{ totalIncome.toFixed(2) }}</p>
          </div>
          <div class="summary-card expense-card">
            <h4>Total Expenses</h4>
            <p class="amount">-${{ totalExpensesOnly.toFixed(2) }}</p>
          </div>
          <div class="summary-card balance-card">
            <h4>Net Balance</h4>
            <p class="amount" :class="netBalance >= 0 ? 'positive' : 'negative'">
              {{ netBalance >= 0 ? '+' : '' }}${{ netBalance.toFixed(2) }}
            </p>
          </div>
        </div>

        <!-- Expenses Table -->
        <div class="expenses-table">
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Category</th>
                <th>Amount</th>
                <th>Payment</th>
                <th>Notes</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="expense in expenses" :key="expense.id">
                <td>{{ formatDate(expense.expense_date) }}</td>
                <td>
                  <span class="type-badge" :class="expense.category?.type">
                    {{ expense.category?.type === 'income' ? '💰 Income' : '💸 Expense' }}
                  </span>
                </td>
                <td>
                  <span class="category-badge" :style="{ borderColor: expense.category?.color }">
                    {{ expense.category?.icon }} {{ expense.category?.name }}
                  </span>
                </td>
                <td class="amount-cell" :class="expense.category?.type">
                  {{ expense.category?.type === 'income' ? '+' : '-' }}${{ parseFloat(expense.amount).toFixed(2) }}
                </td>
                <td>
                  <span class="payment-badge">
                    {{ formatPaymentMethod(expense.payment_method) }}
                  </span>
                </td>
                <td class="notes-cell">{{ expense.notes || '-' }}</td>
                <td class="actions-cell">
                  <button @click="editExpense(expense)" class="btn-icon" title="Edit">
                    ✏️
                  </button>
                  <button @click="deleteExpense(expense.id)" class="btn-icon danger" title="Delete">
                    🗑️
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import api from '../../services/api';
import AppLayout from '../Shared/AppLayout.vue';

export default {
  name: 'ExpenseList',
  components: {
    AppLayout
  },
  setup() {
    const router = useRouter();
    const authStore = useAuthStore();

    const expenses = ref([]);
    const categories = ref([]);
    const loading = ref(false);
    const showCreateForm = ref(false);
    const editingExpense = ref(null);

    // CSV Import
    const showImportModal = ref(false);
    const csvFile = ref(null);
    const csvData = ref([]);
    const csvHeaders = ref([]);
    const columnMapping = ref({
      date: '',
      description: '',
      amount: '',
      type: ''
    });
    const importDefaults = ref({
      category_id: '',
      payment_method: 'bank_transfer'
    });
    const importing = ref(false);
    const importResult = ref(null);
    const fileInput = ref(null);
    const useAICategorization = ref(false);

    const form = ref({
      amount: '',
      date: new Date().toISOString().split('T')[0],
      category_id: '',
      payment_method: '',
      notes: ''
    });

    const filters = ref({
      type: '',
      category_id: '',
      payment_method: '',
      from_date: '',
      to_date: ''
    });

    const expenseCategories = computed(() => {
      return categories.value.filter(cat => cat.type === 'expense');
    });

    const incomeCategories = computed(() => {
      return categories.value.filter(cat => cat.type === 'income');
    });

    const filteredCategories = computed(() => {
      if (!filters.value.type) return categories.value;
      return categories.value.filter(cat => cat.type === filters.value.type);
    });

    const totalIncome = computed(() => {
      return expenses.value
        .filter(exp => exp.category?.type === 'income')
        .reduce((sum, exp) => sum + parseFloat(exp.amount), 0);
    });

    const totalExpensesOnly = computed(() => {
      return expenses.value
        .filter(exp => exp.category?.type === 'expense')
        .reduce((sum, exp) => sum + parseFloat(exp.amount), 0);
    });

    const netBalance = computed(() => {
      return totalIncome.value - totalExpensesOnly.value;
    });

    const totalExpenses = computed(() => {
      return expenses.value.reduce((sum, exp) => sum + parseFloat(exp.amount), 0);
    });

    const monthExpenses = computed(() => {
      const now = new Date();
      const currentMonth = now.getMonth();
      const currentYear = now.getFullYear();
      
      return expenses.value
        .filter(exp => {
          const expDate = new Date(exp.expense_date);
          return expDate.getMonth() === currentMonth && expDate.getFullYear() === currentYear;
        })
        .reduce((sum, exp) => sum + parseFloat(exp.amount), 0);
    });

    const applyFilters = () => {
      // When type filter changes, reset category filter
      filters.value.category_id = '';
      fetchExpenses();
    };

    const fetchCategories = async () => {
      try {
        const response = await api.get('/categories');
        categories.value = response.data.data;
      } catch (error) {
        console.error('Failed to load categories');
      }
    };

    const fetchExpenses = async () => {
      loading.value = true;
      try {
        const params = {};
        if (filters.value.category_id) params.category_id = filters.value.category_id;
        if (filters.value.payment_method) params.payment_method = filters.value.payment_method;
        if (filters.value.from_date) params.from_date = filters.value.from_date;
        if (filters.value.to_date) params.to_date = filters.value.to_date;

        const response = await api.get('/expenses', { params });
        expenses.value = response.data.data;
      } catch (error) {
        alert('Failed to load expenses');
      } finally {
        loading.value = false;
      }
    };

    const createExpense = async () => {
      try {
        // Transform form data to match backend expectations
        const expenseData = {
          category_id: form.value.category_id,
          amount: form.value.amount,
          expense_date: form.value.date,
          payment_method: form.value.payment_method,
          notes: form.value.notes
        };
        
        await api.post('/expenses', expenseData);
        showCreateForm.value = false;
        resetForm();
        fetchExpenses();
      } catch (error) {
        alert(error.response?.data?.message || 'Failed to create expense');
      }
    };

    const editExpense = (expense) => {
      editingExpense.value = expense;
      form.value = {
        amount: expense.amount,
        date: expense.expense_date,
        category_id: expense.category_id,
        payment_method: expense.payment_method,
        notes: expense.notes || ''
      };
      showCreateForm.value = false;
    };

    const updateExpense = async () => {
      try {
        // Transform form data to match backend expectations
        const expenseData = {
          category_id: form.value.category_id,
          amount: form.value.amount,
          expense_date: form.value.date,
          payment_method: form.value.payment_method,
          notes: form.value.notes
        };
        
        await api.put(`/expenses/${editingExpense.value.id}`, expenseData);
        editingExpense.value = null;
        resetForm();
        fetchExpenses();
      } catch (error) {
        alert(error.response?.data?.message || 'Failed to update expense');
      }
    };

    const deleteExpense = async (id) => {
      if (!confirm('Are you sure you want to delete this expense?')) return;

      try {
        await api.delete(`/expenses/${id}`);
        fetchExpenses();
      } catch (error) {
        alert('Failed to delete expense');
      }
    };

    const cancelForm = () => {
      showCreateForm.value = false;
      editingExpense.value = null;
      resetForm();
    };

    const resetForm = () => {
      form.value = {
        amount: '',
        date: new Date().toISOString().split('T')[0],
        category_id: '',
        payment_method: '',
        notes: ''
      };
    };

    const clearFilters = () => {
      filters.value = {
        type: '',
        category_id: '',
        payment_method: '',
        from_date: '',
        to_date: ''
      };
      fetchExpenses();
    };

    const formatDate = (dateString) => {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    };

    const formatPaymentMethod = (method) => {
      const methods = {
        cash: '💵 Cash',
        card: '💳 Card',
        bank_transfer: '🏦 Bank',
        digital_wallet: '📱 Wallet',
        other: '📝 Other'
      };
      return methods[method] || method;
    };

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(amount);
    };

    // CSV/PDF Import Functions
    const handleFileSelect = (event) => {
      const file = event.target.files[0];
      if (file && (file.type === 'text/csv' || file.name.endsWith('.csv'))) {
        csvFile.value = file;
        parseCSV(file);
      } else if (file && (file.type === 'application/pdf' || file.name.endsWith('.pdf'))) {
        csvFile.value = file;
        parsePDF(file);
      } else {
        alert('Please select a valid CSV or PDF file');
      }
    };

    const parseCSV = (file) => {
      const reader = new FileReader();
      reader.onload = (e) => {
        const text = e.target.result;
        const lines = text.split('\n').filter(line => line.trim());
        
        if (lines.length === 0) return;

        // Parse headers
        csvHeaders.value = lines[0].split(',').map(h => h.trim().replace(/['"]/g, ''));
        
        // Parse data rows
        const rows = [];
        for (let i = 1; i < lines.length; i++) {
          const values = lines[i].split(',').map(v => v.trim().replace(/['"]/g, ''));
          const row = {};
          csvHeaders.value.forEach((header, index) => {
            row[header] = values[index] || '';
          });
          rows.push(row);
        }
        
        csvData.value = rows;

        // Auto-detect column mapping
        autoDetectColumns();
      };
      reader.readAsText(file);
    };

    const parsePDF = async (file) => {
      try {
        // Show loading message
        alert('🤖 Analyzing PDF with AI... This may take a moment.');

        // Convert PDF to base64
        const reader = new FileReader();
        reader.onload = async (e) => {
          try {
            const base64 = e.target.result.split(',')[1]; // Remove data:application/pdf;base64, prefix

            // Send to AI for analysis
            const response = await api.post('/expenses/analyze-pdf', {
              pdf: base64
            });

            if (response.data.success) {
              const transactions = response.data.data;
              
              if (transactions.length === 0) {
                alert('No transactions found in the PDF. Please make sure it\'s a bank statement.');
                removeFile();
                return;
              }

              // Convert AI response to our format
              csvHeaders.value = ['Date', 'Description', 'Amount', 'Category'];
              csvData.value = transactions.map(t => ({
                'Date': t.date,
                'Description': t.description,
                'Amount': t.amount,
                'Category': t.category_name || 'Uncategorized',
                '_ai_category_id': t.category_id,
                '_ai_category_icon': t.category_icon
              }));

              // Set column mapping
              columnMapping.value.date = 'Date';
              columnMapping.value.description = 'Description';
              columnMapping.value.amount = 'Amount';
              columnMapping.value.type = '';

              // Disable AI categorization toggle since PDF already categorized by AI
              useAICategorization.value = false;

              alert(`✅ Successfully extracted ${transactions.length} transactions using AI!`);
            } else {
              alert('Failed to analyze PDF: ' + (response.data.message || 'Unknown error'));
              removeFile();
            }
          } catch (error) {
            console.error('AI PDF analysis error:', error);
            const errorMsg = error.response?.data?.message || error.message || 'Unknown error';
            alert('Failed to analyze PDF: ' + errorMsg + '\n\nMake sure your OpenAI API key is configured in .env file.');
            removeFile();
          }
        };
        reader.readAsDataURL(file);

      } catch (error) {
        console.error('PDF processing error:', error);
        alert('Failed to process PDF file.');
        removeFile();
      }
    };

    const autoDetectColumns = () => {
      const headers = csvHeaders.value.map(h => h.toLowerCase());
      
      // Try to detect date column
      const dateKeywords = ['date', 'transaction date', 'posted date', 'dt'];
      columnMapping.value.date = csvHeaders.value.find((h, i) => 
        dateKeywords.some(keyword => headers[i].includes(keyword))
      ) || '';

      // Try to detect description column
      const descKeywords = ['description', 'memo', 'details', 'transaction', 'merchant'];
      columnMapping.value.description = csvHeaders.value.find((h, i) => 
        descKeywords.some(keyword => headers[i].includes(keyword))
      ) || '';

      // Try to detect amount column
      const amountKeywords = ['amount', 'total', 'value', 'sum', 'debit', 'credit'];
      columnMapping.value.amount = csvHeaders.value.find((h, i) => 
        amountKeywords.some(keyword => headers[i].includes(keyword))
      ) || '';

      // Try to detect type column
      const typeKeywords = ['type', 'transaction type', 'dr/cr', 'debit/credit'];
      columnMapping.value.type = csvHeaders.value.find((h, i) => 
        typeKeywords.some(keyword => headers[i].includes(keyword))
      ) || '';
    };

    const previewRows = computed(() => {
      if (!csvData.value.length || !columnMapping.value.date) return [];
      
      return csvData.value.slice(0, 5).map(row => {
        let amount = row[columnMapping.value.amount] || '0';
        // Remove currency symbols and commas
        amount = amount.replace(/[$,]/g, '');
        
        const description = row[columnMapping.value.description] || '';
        
        // Check if amount is negative (indicates expense)
        let type = 'expense';
        if (columnMapping.value.type && row[columnMapping.value.type]) {
          const typeValue = row[columnMapping.value.type].toLowerCase();
          if (typeValue.includes('credit') || typeValue.includes('income') || typeValue.includes('deposit')) {
            type = 'income';
          }
        } else if (parseFloat(amount) > 0) {
          type = 'expense';
        }

        // Check if AI already categorized (from PDF)
        let categoryDisplay = 'Default';
        if (row._ai_category_id) {
          const aiCategory = categories.value.find(c => c.id === row._ai_category_id);
          if (aiCategory) {
            categoryDisplay = `${aiCategory.icon} ${aiCategory.name} 🤖`;
          }
        } else {
          // Detect category using keywords
          const detectedCategoryId = detectCategory(description);
          const detectedCategory = categories.value.find(c => c.id === detectedCategoryId);
          if (detectedCategory) {
            categoryDisplay = `${detectedCategory.icon} ${detectedCategory.name}`;
          }
        }

        return {
          date: row[columnMapping.value.date] || '',
          description: description,
          amount: Math.abs(parseFloat(amount)).toFixed(2),
          type: type,
          category: categoryDisplay
        };
      });
    });

    // Auto-detect category based on description
    const detectCategory = (description) => {
      if (!description) return importDefaults.value.category_id || null;

      const desc = description.toLowerCase();
      
      // Define category keywords
      const categoryKeywords = {
        // Expenses
        'groceries': ['grocery', 'supermarket', 'walmart', 'target', 'costco', 'whole foods', 'trader joe', 'safeway', 'kroger'],
        'restaurants': ['restaurant', 'cafe', 'coffee', 'starbucks', 'mcdonald', 'burger', 'pizza', 'dining', 'food delivery', 'ubereats', 'doordash'],
        'transportation': ['gas', 'fuel', 'uber', 'lyft', 'taxi', 'parking', 'toll', 'metro', 'transit', 'car wash'],
        'utilities': ['electric', 'water', 'gas bill', 'internet', 'phone bill', 'cable', 'utility'],
        'entertainment': ['movie', 'cinema', 'netflix', 'spotify', 'hulu', 'disney', 'gaming', 'theater', 'concert'],
        'shopping': ['amazon', 'ebay', 'online shopping', 'mall', 'store', 'retail'],
        'health': ['pharmacy', 'doctor', 'hospital', 'medical', 'dental', 'health', 'gym', 'fitness'],
        'bills': ['bill payment', 'insurance', 'subscription', 'membership'],
        // Income
        'salary': ['salary', 'payroll', 'wages', 'paycheck'],
        'freelance': ['freelance', 'consulting', 'contract', 'gig'],
        'investment': ['dividend', 'interest', 'stock', 'investment return']
      };

      // Find matching category
      for (const [keyword, patterns] of Object.entries(categoryKeywords)) {
        for (const pattern of patterns) {
          if (desc.includes(pattern)) {
            // Find category by name (case insensitive)
            const category = categories.value.find(cat => 
              cat.name.toLowerCase().includes(keyword) || 
              keyword.includes(cat.name.toLowerCase())
            );
            if (category) return category.id;
          }
        }
      }

      // Return default category if no match
      return importDefaults.value.category_id || null;
    };

    const canImport = computed(() => {
      return csvData.value.length > 0 && 
             columnMapping.value.date && 
             columnMapping.value.amount &&
             !importing.value;
    });

    const importTransactions = async () => {
      if (!canImport.value) return;

      importing.value = true;
      importResult.value = null;

      try {
        let transactions = csvData.value.map(row => {
          let amount = row[columnMapping.value.amount] || '0';
          amount = amount.replace(/[$,]/g, '');
          
          const description = row[columnMapping.value.description] || 'Imported from CSV';
          
          let type = 'expense';
          if (columnMapping.value.type && row[columnMapping.value.type]) {
            const typeValue = row[columnMapping.value.type].toLowerCase();
            if (typeValue.includes('credit') || typeValue.includes('income') || typeValue.includes('deposit')) {
              type = 'income';
            }
          }

          // Parse date
          let dateStr = row[columnMapping.value.date];
          let parsedDate = new Date(dateStr);
          if (isNaN(parsedDate.getTime())) {
            // Try common date formats
            const parts = dateStr.split(/[-/]/);
            if (parts.length === 3) {
              // Try MM/DD/YYYY or DD/MM/YYYY
              parsedDate = new Date(parts[2], parts[0] - 1, parts[1]);
            }
          }

          // Check if this row was already categorized by AI (from PDF)
          const aiCategoryId = row._ai_category_id;

          return {
            amount: Math.abs(parseFloat(amount)),
            expense_date: parsedDate.toISOString().split('T')[0],
            description: description,
            payment_method: importDefaults.value.payment_method,
            notes: description,
            _ai_category_id: aiCategoryId // Preserve AI category if exists
          };
        });

        // Use AI categorization if enabled AND not already categorized (PDF was already AI-categorized)
        const needsAICategorization = useAICategorization.value && !transactions[0]?._ai_category_id;
        
        if (needsAICategorization) {
          try {
            const aiResponse = await api.post('/expenses/ai-categorize', {
              transactions: transactions.map(t => ({
                description: t.description,
                amount: t.amount
              }))
            });

            if (aiResponse.data.success) {
              transactions = transactions.map((t, index) => ({
                ...t,
                category_id: aiResponse.data.data[index]?.category_id || detectCategory(t.description)
              }));
            } else {
              // Fallback to keyword detection
              transactions = transactions.map(t => ({
                ...t,
                category_id: detectCategory(t.description)
              }));
            }
          } catch (aiError) {
            console.warn('AI categorization failed, using keyword detection:', aiError);
            // Fallback to keyword detection
            transactions = transactions.map(t => ({
              ...t,
              category_id: detectCategory(t.description)
            }));
          }
        } else if (transactions[0]?._ai_category_id) {
          // Use AI categories from PDF
          transactions = transactions.map(t => ({
            ...t,
            category_id: t._ai_category_id || detectCategory(t.description)
          }));
        } else {
          // Use keyword detection
          transactions = transactions.map(t => ({
            ...t,
            category_id: detectCategory(t.description)
          }));
        }

        const response = await api.post('/expenses/import-csv', {
          transactions: transactions
        });

        importResult.value = {
          success: true,
          message: useAICategorization.value 
            ? '✨ AI categorization completed! Import successful!' 
            : 'Import successful!',
          imported: response.data.imported || 0,
          duplicates: response.data.duplicates || 0
        };

        // Refresh expenses list
        await fetchExpenses();

        // Close modal after 3 seconds
        setTimeout(() => {
          closeImportModal();
        }, 3000);

      } catch (error) {
        importResult.value = {
          success: false,
          message: error.response?.data?.message || 'Import failed. Please try again.'
        };
      } finally {
        importing.value = false;
      }
    };

    const removeFile = () => {
      csvFile.value = null;
      csvData.value = [];
      csvHeaders.value = [];
      columnMapping.value = { date: '', description: '', amount: '', type: '' };
      importResult.value = null;
      if (fileInput.value) {
        fileInput.value.value = '';
      }
    };

    const closeImportModal = () => {
      showImportModal.value = false;
      removeFile();
    };

    const totals = computed(() => {
      return {
        income: totalIncome.value,
        expense: totalExpensesOnly.value,
        balance: netBalance.value
      };
    });

    onMounted(() => {
      fetchCategories();
      fetchExpenses();
    });

    return {
      expenses,
      categories,
      loading,
      showCreateForm,
      editingExpense,
      form,
      filters,
      expenseCategories,
      incomeCategories,
      filteredCategories,
      totalIncome,
      totalExpensesOnly,
      netBalance,
      totalExpenses,
      monthExpenses,
      createExpense,
      editExpense,
      updateExpense,
      deleteExpense,
      cancelForm,
      clearFilters,
      applyFilters,
      fetchExpenses,
      formatDate,
      formatPaymentMethod,
      formatCurrency,
      totals,
      // CSV Import
      showImportModal,
      csvFile,
      csvData,
      csvHeaders,
      columnMapping,
      importDefaults,
      importing,
      importResult,
      fileInput,
      useAICategorization,
      handleFileSelect,
      parseCSV,
      parsePDF,
      autoDetectColumns,
      detectCategory,
      previewRows,
      canImport,
      importTransactions,
      removeFile,
      closeImportModal
    };
  }
};
</script>

<style scoped>
.transactions-content {
  width: 100%;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.75rem;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary .btn-icon {
  color: white !important;
  font-size: 1.4rem;
  line-height: 1;
  display: inline-block;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

/* Summary Cards */
.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.summary-card {
  background: white;
  padding: 1.75rem;
  border-radius: 16px;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transition: transform 0.3s ease;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.summary-card:hover {
  transform: translateY(-4px);
}

.summary-card.income {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border: none;
}

.summary-card.expense {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  border: none;
}

.summary-card.balance {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
}

.summary-icon {
  font-size: 2.5rem;
  flex-shrink: 0;
  filter: brightness(1.2);
}

.summary-info {
  flex: 1;
}

.summary-label {
  font-size: 0.875rem;
  color: rgba(255, 255, 255, 0.9);
  margin: 0 0 0.5rem 0;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.summary-value {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0;
  color: white;
}

.summary-card.income .summary-value {
  color: white;
}

.summary-card.expense .summary-value {
  color: white;
}

.summary-card.balance .summary-value.positive {
  color: white;
}

.summary-card.balance .summary-value.negative {
  color: white;
}

/* Header Actions */
.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

/* Filters Row */
.filters-row {
  margin-bottom: 2rem;
}

.filters-section {
  background: white;
  padding: 1.5rem;
  border-radius: 16px;
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  align-items: flex-end;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #555;
}

.filter-group select,
.filter-group input {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 5px;
  font-size: 0.9rem;
}

.form-card {
  background: white;
  padding: 2rem;
  border-radius: 10px;
  margin-bottom: 2rem;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.form-card h3 {
  margin-top: 0;
  color: #667eea;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 600;
  color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 1rem;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.btn-primary, .btn-secondary {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s;
  font-weight: 600;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
  background: #e0e0e0;
  color: #333;
}

.btn-secondary:hover {
  background: #d0d0d0;
}

.loading, .empty-state {
  text-align: center;
  padding: 3rem;
  background: white;
  border-radius: 10px;
  color: #999;
}

.empty-state p:first-child {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.expenses-container {
  background: white;
  padding: 2rem;
  border-radius: 10px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}

.summary-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1.5rem;
  border-radius: 10px;
  text-align: center;
}

.summary-card.income-card {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.summary-card.expense-card {
  background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
}

.summary-card.balance-card {
  background: linear-gradient(135deg, #4776e6 0%, #8e54e9 100%);
}

.summary-card h4 {
  margin: 0 0 0.5rem 0;
  font-size: 0.9rem;
  opacity: 0.9;
}

.summary-card .amount {
  font-size: 2rem;
  font-weight: bold;
  margin: 0;
}

.expenses-table {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead {
  background: #f9f9f9;
}

th {
  text-align: left;
  padding: 1rem;
  font-weight: 600;
  color: #555;
  border-bottom: 2px solid #e0e0e0;
}

td {
  padding: 1rem;
  border-bottom: 1px solid #f0f0f0;
}

.category-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  border: 2px solid;
  font-size: 0.9rem;
  font-weight: 600;
}

.payment-badge {
  display: inline-block;
  padding: 4px 10px;
  background: #f0f0f0;
  border-radius: 15px;
  font-size: 0.85rem;
}

.amount-cell {
  font-weight: 600;
  font-size: 1.1rem;
}

.amount-cell.expense {
  color: #c62828;
}

.amount-cell.income {
  color: #2e7d32;
}

.amount-cell .positive {
  color: #2e7d32;
}

.amount-cell .negative {
  color: #c62828;
}

.type-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 15px;
  font-size: 0.85rem;
  font-weight: 600;
}

.type-badge.expense {
  background: #ffebee;
  color: #c62828;
}

.type-badge.income {
  background: #e8f5e9;
  color: #2e7d32;
}

.notes-cell {
  color: #666;
  max-width: 200px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
}

.actions-cell .btn-icon {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 5px;
  transition: background 0.3s;
  color: inherit;
}

.actions-cell .btn-icon:hover {
  background: #f0f0f0;
}

.actions-cell .btn-icon.danger:hover {
  background: #ffebee;
}

/* CSV Import Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 2rem;
}

.modal-content {
  background: white;
  border-radius: 16px;
  max-width: 900px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem;
  border-bottom: 2px solid #f3f4f6;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.5rem;
  color: #333;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #666;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #333;
}

.modal-body {
  padding: 2rem;
}

.import-instructions {
  background: #f0f9ff;
  border-left: 4px solid #667eea;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.import-instructions h4 {
  margin: 0 0 1rem 0;
  color: #667eea;
}

.import-instructions ol {
  margin: 0;
  padding-left: 1.5rem;
}

.import-instructions li {
  margin: 0.5rem 0;
  color: #555;
}

.upload-zone {
  margin: 2rem 0;
}

.upload-area {
  border: 3px dashed #d1d5db;
  border-radius: 12px;
  padding: 3rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s;
  background: #f9fafb;
}

.upload-area:hover {
  border-color: #667eea;
  background: #f0f9ff;
}

.upload-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.upload-text {
  font-size: 1.25rem;
  font-weight: 600;
  color: #333;
  margin: 0.5rem 0;
}

.upload-hint {
  color: #6b7280;
  margin: 0;
}

.csv-preview {
  margin-top: 2rem;
}

.file-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.file-name {
  font-weight: 600;
  color: #333;
}

.btn-text {
  background: none;
  border: none;
  color: #ef4444;
  cursor: pointer;
  font-weight: 600;
  padding: 0.5rem 1rem;
}

.btn-text:hover {
  text-decoration: underline;
}

.column-mapping, .default-settings {
  margin-bottom: 2rem;
}

.column-mapping h4, .default-settings h4 {
  margin: 0 0 1rem 0;
  color: #333;
  font-size: 1.1rem;
}

.ai-toggle {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1.25rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  color: white;
}

.toggle-label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  margin-bottom: 0.5rem;
}

.toggle-label input[type="checkbox"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
  accent-color: white;
}

.toggle-text {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.05rem;
}

.ai-badge {
  background: rgba(255, 255, 255, 0.3);
  padding: 0.15rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.ai-description {
  margin: 0.5rem 0 0 2rem;
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.4;
}

.auto-detect-notice {
  background: #ecfdf5;
  border-left: 4px solid #10b981;
  padding: 1rem;
  border-radius: 6px;
  margin-bottom: 1rem;
  color: #065f46;
  font-size: 0.95rem;
}

.auto-detect-notice strong {
  color: #047857;
}

.help-text {
  font-size: 0.85rem;
  color: #6b7280;
  font-style: italic;
  margin-top: 0.25rem;
  display: block;
}

.mapping-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.mapping-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.mapping-item label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #555;
}

.mapping-item select, .default-settings select {
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  cursor: pointer;
}

.preview-section {
  margin-top: 2rem;
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 12px;
}

.preview-section h4 {
  margin: 0 0 1rem 0;
  color: #333;
}

.preview-table {
  overflow-x: auto;
}

.preview-table table {
  width: 100%;
  border-collapse: collapse;
}

.preview-table th {
  background: #667eea;
  color: white;
  padding: 0.75rem;
  text-align: left;
  font-weight: 600;
}

.preview-table td {
  padding: 0.75rem;
  border-bottom: 1px solid #e5e7eb;
}

.preview-table tr:hover {
  background: white;
}

.category-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #0369a1;
}

.preview-note {
  margin-top: 1rem;
  color: #6b7280;
  font-style: italic;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 2rem;
  border-top: 2px solid #f3f4f6;
}

.import-result {
  margin-top: 1rem;
  padding: 1rem;
  border-radius: 8px;
  font-weight: 600;
}

.import-result.success {
  background: #e8f5e9;
  color: #2e7d32;
  border: 2px solid #4caf50;
}

.import-result.error {
  background: #ffebee;
  color: #c62828;
  border: 2px solid #ef4444;
}

.import-result p {
  margin: 0.25rem 0;
}

@media (max-width: 768px) {
  .mapping-grid {
    grid-template-columns: 1fr;
  }
  
  .modal-overlay {
    padding: 1rem;
  }
}
</style>
