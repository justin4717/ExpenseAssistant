<template>
  <AppLayout page-title="Budgets" page-icon="💰" page-subtitle="Set and track your spending limits">
    <div class="budgets-content">
      <!-- Budget Status Overview -->
      <div v-if="budgets.length > 0" class="budget-status-grid">
        <div v-for="budget in budgetStatus" :key="budget.id" class="budget-status-card" :class="`status-${budget.status}`">
          <div class="budget-header">
            <div>
              <h3>{{ budget.name }}</h3>
              <span v-if="budget.category" class="category-badge">
                {{ budget.category.icon }} {{ budget.category.name }}
              </span>
              <span v-else class="category-badge">Overall Budget</span>
            </div>
            <span class="period-badge">{{ budget.period }}</span>
          </div>

          <div class="budget-amounts">
            <div class="amount-item">
              <span class="label">Budget</span>
              <span class="value">{{ formatCurrency(budget.amount) }}</span>
            </div>
            <div class="amount-item">
              <span class="label">Spent</span>
              <span class="value spent">{{ formatCurrency(budget.spent) }}</span>
            </div>
            <div class="amount-item">
              <span class="label">Remaining</span>
              <span class="value" :class="budget.remaining < 0 ? 'exceeded' : 'remaining'">
                {{ formatCurrency(budget.remaining) }}
              </span>
            </div>
          </div>

          <div class="progress-section">
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: Math.min(budget.percentage, 100) + '%' }"></div>
            </div>
            <span class="percentage">{{ budget.percentage }}%</span>
          </div>

          <div class="budget-actions">
            <button @click="editBudget(budget)" class="btn-icon" title="Edit">
              ✏️
            </button>
            <button @click="deleteBudget(budget.id)" class="btn-icon danger" title="Delete">
              🗑️
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state">
        <div class="empty-icon">💰</div>
        <h3>No Budgets Set</h3>
        <p>Create your first budget to start tracking your spending limits</p>
      </div>

      <!-- Add Budget Button -->
      <div class="add-budget-section">
        <button @click="showCreateForm = true" class="btn-primary">
          <span class="btn-icon">➕</span>
          Create Budget
        </button>
      </div>

      <!-- Create/Edit Form -->
      <div v-if="showCreateForm || editingBudget" class="form-card">
        <h3>{{ editingBudget ? 'Edit Budget' : 'Create New Budget' }}</h3>
        <form @submit.prevent="editingBudget ? updateBudget() : createBudget()">
          <div class="form-row">
            <div class="form-group">
              <label>Budget Name *</label>
              <input v-model="form.name" type="text" required placeholder="e.g., Monthly Groceries" />
            </div>

            <div class="form-group">
              <label>Amount *</label>
              <input v-model.number="form.amount" type="number" step="0.01" required placeholder="500.00" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Category (Optional)</label>
              <select v-model="form.category_id">
                <option value="">Overall Budget</option>
                <option v-for="cat in expenseCategories" :key="cat.id" :value="cat.id">
                  {{ cat.icon }} {{ cat.name }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Period *</label>
              <select v-model="form.period" required>
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Start Date *</label>
              <input v-model="form.start_date" type="date" required />
            </div>

            <div class="form-group">
              <label>End Date (Optional)</label>
              <input v-model="form.end_date" type="date" />
            </div>
          </div>

          <div class="form-actions">
            <button type="button" @click="cancelForm" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary">
              {{ editingBudget ? 'Update Budget' : 'Create Budget' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import AppLayout from '../Shared/AppLayout.vue';
import api from '../../services/api';

export default {
  name: 'BudgetList',
  components: {
    AppLayout
  },
  setup() {
    const budgets = ref([]);
    const budgetStatus = ref([]);
    const categories = ref([]);
    const showCreateForm = ref(false);
    const editingBudget = ref(null);

    const form = ref({
      name: '',
      category_id: '',
      amount: '',
      period: 'monthly',
      start_date: new Date().toISOString().split('T')[0],
      end_date: ''
    });

    const expenseCategories = computed(() => {
      return categories.value.filter(cat => cat.type === 'expense');
    });

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2
      }).format(amount);
    };

    const fetchBudgets = async () => {
      try {
        const response = await api.get('/budgets');
        budgets.value = response.data.data;
      } catch (error) {
        console.error('Error fetching budgets:', error);
      }
    };

    const fetchBudgetStatus = async () => {
      try {
        const response = await api.get('/budgets-status');
        budgetStatus.value = response.data.data;
      } catch (error) {
        console.error('Error fetching budget status:', error);
      }
    };

    const fetchCategories = async () => {
      try {
        const response = await api.get('/categories');
        categories.value = response.data.data;
      } catch (error) {
        console.error('Error fetching categories:', error);
      }
    };

    const createBudget = async () => {
      try {
        await api.post('/budgets', form.value);
        showCreateForm.value = false;
        resetForm();
        fetchBudgets();
        fetchBudgetStatus();
      } catch (error) {
        console.error('Error creating budget:', error);
        alert('Failed to create budget');
      }
    };

    const editBudget = (budget) => {
      editingBudget.value = budget;
      form.value = {
        name: budget.name,
        category_id: budget.category?.id || '',
        amount: budget.amount,
        period: budget.period,
        start_date: budget.start_date,
        end_date: budget.end_date || ''
      };
      showCreateForm.value = false;
    };

    const updateBudget = async () => {
      try {
        await api.put(`/budgets/${editingBudget.value.id}`, form.value);
        editingBudget.value = null;
        resetForm();
        fetchBudgets();
        fetchBudgetStatus();
      } catch (error) {
        console.error('Error updating budget:', error);
        alert('Failed to update budget');
      }
    };

    const deleteBudget = async (id) => {
      if (!confirm('Are you sure you want to delete this budget?')) return;

      try {
        await api.delete(`/budgets/${id}`);
        fetchBudgets();
        fetchBudgetStatus();
      } catch (error) {
        console.error('Error deleting budget:', error);
        alert('Failed to delete budget');
      }
    };

    const cancelForm = () => {
      showCreateForm.value = false;
      editingBudget.value = null;
      resetForm();
    };

    const resetForm = () => {
      form.value = {
        name: '',
        category_id: '',
        amount: '',
        period: 'monthly',
        start_date: new Date().toISOString().split('T')[0],
        end_date: ''
      };
    };

    onMounted(() => {
      fetchBudgets();
      fetchBudgetStatus();
      fetchCategories();
    });

    return {
      budgets,
      budgetStatus,
      categories,
      expenseCategories,
      showCreateForm,
      editingBudget,
      form,
      formatCurrency,
      createBudget,
      editBudget,
      updateBudget,
      deleteBudget,
      cancelForm
    };
  }
};
</script>

<style scoped>
.budgets-content {
  width: 100%;
}

.budget-status-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 2rem;
  margin-bottom: 3rem;
}

.budget-status-card {
  background: white;
  padding: 2rem;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  border-left: 5px solid;
  transition: transform 0.3s ease;
}

.budget-status-card:hover {
  transform: translateY(-4px);
}

.budget-status-card.status-good {
  border-left-color: #10b981;
}

.budget-status-card.status-warning {
  border-left-color: #f59e0b;
}

.budget-status-card.status-exceeded {
  border-left-color: #ef4444;
}

.budget-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 1.5rem;
}

.budget-header h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  color: #333;
}

.category-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #f3f4f6;
  border-radius: 12px;
  font-size: 0.875rem;
  color: #666;
}

.period-badge {
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.budget-amounts {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.amount-item {
  display: flex;
  flex-direction: column;
}

.amount-item .label {
  font-size: 0.75rem;
  color: #9ca3af;
  margin-bottom: 0.25rem;
  text-transform: uppercase;
  font-weight: 600;
}

.amount-item .value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #333;
}

.amount-item .value.spent {
  color: #ef4444;
}

.amount-item .value.remaining {
  color: #10b981;
}

.amount-item .value.exceeded {
  color: #ef4444;
}

.progress-section {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.progress-bar {
  flex: 1;
  height: 12px;
  background: #e5e7eb;
  border-radius: 6px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
  transition: width 0.3s ease;
}

.status-warning .progress-fill {
  background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
}

.status-exceeded .progress-fill {
  background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
}

.percentage {
  font-weight: 700;
  color: #333;
  min-width: 50px;
  text-align: right;
}

.budget-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
}

.empty-icon {
  font-size: 5rem;
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.5rem;
  color: #333;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: #666;
  font-size: 1rem;
}

.add-budget-section {
  margin-bottom: 2rem;
}

.btn-primary {
  display: inline-flex;
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

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.btn-primary .btn-icon {
  color: white !important;
  font-size: 1.4rem;
  line-height: 1;
  display: inline-block;
}

.btn-secondary {
  padding: 0.75rem 1.75rem;
  border-radius: 12px;
  background: #f3f4f6;
  color: #333;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-icon {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 5px;
  transition: background 0.3s;
}

.btn-icon:hover {
  background: #f0f0f0;
}

.btn-icon.danger:hover {
  background: #ffebee;
}

.form-card {
  background: white;
  padding: 2rem;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
}

.form-card h3 {
  margin: 0 0 1.5rem 0;
  font-size: 1.5rem;
  color: #333;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #333;
  font-size: 0.9rem;
}

.form-group input,
.form-group select {
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.3s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #667eea;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
}

@media (max-width: 768px) {
  .budget-status-grid {
    grid-template-columns: 1fr;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
