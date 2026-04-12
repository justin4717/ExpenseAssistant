<template>
  <AppLayout 
    page-title="Dashboard" 
    page-icon="📊"
    :page-subtitle="`Welcome back, ${user?.name}! 👋`"
  >
    <div class="dashboard-content">
      <div class="stats-grid">
        <div class="stat-card stat-income">
          <div class="stat-header">
            <div class="stat-icon">💰</div>
            <h3>Total Income</h3>
          </div>
          <p class="stat-value">{{ formatCurrency(totalIncome) }}</p>
          <span class="stat-label">All time</span>
        </div>

        <div class="stat-card stat-expense">
          <div class="stat-header">
            <div class="stat-icon">💸</div>
            <h3>Total Expenses</h3>
          </div>
          <p class="stat-value">{{ formatCurrency(totalExpenses) }}</p>
          <span class="stat-label">All time</span>
        </div>

        <div class="stat-card stat-balance">
          <div class="stat-header">
            <div class="stat-icon">📊</div>
            <h3>Net Balance</h3>
          </div>
          <p class="stat-value">{{ formatCurrency(netBalance) }}</p>
          <span class="stat-label">Current</span>
        </div>

        <div class="stat-card stat-month">
          <div class="stat-header">
            <div class="stat-icon">📅</div>
            <h3>This Month</h3>
          </div>
          <p class="stat-value">{{ formatCurrency(Math.abs(monthTotal)) }}</p>
          <span class="stat-label">{{ monthLabel }}</span>
        </div>
      </div>

      <!-- Budget Warnings -->
      <div v-if="budgetWarnings.length > 0" class="budget-warnings-section">
        <h3>Budget Alerts</h3>
        <div class="warnings-grid">
          <div v-for="warning in budgetWarnings" :key="warning.id" class="warning-card" :class="`alert-${warning.status}`">
            <div class="warning-icon">{{ warning.status === 'exceeded' ? '🚨' : '⚠️' }}</div>
            <div class="warning-content">
              <h4>{{ warning.name }}</h4>
              <p class="warning-message">
                <span v-if="warning.status === 'exceeded'">
                  Budget exceeded! Spent {{ formatCurrency(warning.spent) }} of {{ formatCurrency(warning.amount) }}
                </span>
                <span v-else>
                  {{ warning.percentage }}% used - {{ formatCurrency(warning.remaining) }} remaining
                </span>
              </p>
              <div class="warning-progress">
                <div class="warning-bar" :style="{ width: Math.min(warning.percentage, 100) + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="charts-section">
        <div class="chart-card">
          <h3>Monthly Overview</h3>
          <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
        </div>
      </div>

      <div class="dashboard-actions">
        <div class="action-section">
          <h3>Quick Actions</h3>
          <div class="action-grid">
            <router-link to="/expenses" class="action-card primary">
              <div class="action-icon">➕</div>
              <div class="action-text">
                <h4>Add Transaction</h4>
                <p>Record income or expense</p>
              </div>
            </router-link>
            
            <router-link to="/categories" class="action-card secondary">
              <div class="action-icon">🏷️</div>
              <div class="action-text">
                <h4>Manage Categories</h4>
                <p>Organize your finances</p>
              </div>
            </router-link>
            
            <router-link to="/expenses" class="action-card tertiary">
              <div class="action-icon">📈</div>
              <div class="action-text">
                <h4>View Reports</h4>
                <p>Analyze your spending</p>
              </div>
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { computed, onMounted, ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import AppLayout from './Shared/AppLayout.vue';
import api from '../services/api';
import { Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

export default {
  name: 'Dashboard',
  components: {
    AppLayout,
    Bar
  },
  setup() {
    const authStore = useAuthStore();
    const user = computed(() => authStore.user);
    
    const totalIncome = ref(0);
    const totalExpenses = ref(0);
    const netBalance = ref(0);
    const monthTotal = ref(0);
    const monthLabel = ref('');
    const chartData = ref(null);
    const budgetWarnings = ref([]);

    const chartOptions = {
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: 2.5,
      plugins: {
        legend: {
          position: 'bottom',
        },
        title: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return '$' + value.toLocaleString();
            }
          }
        }
      }
    };

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2
      }).format(amount);
    };

    const fetchDashboardData = async () => {
      try {
        console.log('Fetching dashboard data...');
        const response = await api.get('/expenses');
        console.log('API Response:', response.data);
        
        const expenses = response.data.data || response.data;

        if (!expenses || expenses.length === 0) {
          console.log('No transactions found');
          return;
        }

        // Calculate totals
        let income = 0;
        let expense = 0;
        const monthlyData = {};

        expenses.forEach(item => {
          const category = item.category;
          const amount = parseFloat(item.amount);
          
          console.log('Processing transaction:', item.id, 'Amount:', amount, 'Type:', category?.type);
          
          if (category && category.type === 'income') {
            income += amount;
          } else {
            expense += amount;
          }

          // Group by month for chart
          const date = new Date(item.date);
          const monthKey = date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
          
          if (!monthlyData[monthKey]) {
            monthlyData[monthKey] = { income: 0, expense: 0 };
          }

          if (category && category.type === 'income') {
            monthlyData[monthKey].income += amount;
          } else {
            monthlyData[monthKey].expense += amount;
          }
        });

        console.log('Totals - Income:', income, 'Expense:', expense);

        totalIncome.value = income;
        totalExpenses.value = expense;
        netBalance.value = income - expense;

        // Calculate current month
        const currentMonth = new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        const currentMonthKey = new Date().toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        const currentMonthData = monthlyData[currentMonthKey] || { income: 0, expense: 0 };
        monthTotal.value = currentMonthData.income - currentMonthData.expense;
        monthLabel.value = monthTotal.value >= 0 ? `${currentMonth} Surplus` : `${currentMonth} Deficit`;

        // Prepare chart data (last 6 months)
        const months = Object.keys(monthlyData).slice(-6);
        const incomeData = months.map(month => monthlyData[month].income);
        const expenseData = months.map(month => monthlyData[month].expense);

        console.log('Chart data months:', months);

        chartData.value = {
          labels: months,
          datasets: [
            {
              label: 'Income',
              backgroundColor: '#10b981',
              data: incomeData
            },
            {
              label: 'Expenses',
              backgroundColor: '#ef4444',
              data: expenseData
            }
          ]
        };

        console.log('Dashboard data loaded successfully');
      } catch (error) {
        console.error('Error fetching dashboard data:', error);
        console.error('Error details:', error.response?.data);
      }
    };

    const fetchBudgetWarnings = async () => {
      try {
        const response = await api.get('/budgets-status');
        const budgets = response.data.data;
        
        // Only show budgets that are warning or exceeded
        budgetWarnings.value = budgets.filter(b => 
          b.status === 'warning' || b.status === 'exceeded'
        );
      } catch (error) {
        console.error('Error fetching budget warnings:', error);
      }
    };

    onMounted(() => {
      fetchDashboardData();
      fetchBudgetWarnings();
    });

    return {
      user,
      totalIncome,
      totalExpenses,
      netBalance,
      monthTotal,
      monthLabel,
      chartData,
      chartOptions,
      budgetWarnings,
      formatCurrency
    };
  }
};
</script>

<style scoped>
.dashboard-content {
  width: 100%;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2rem;
  margin-bottom: 3rem;
}

.stat-card {
  background: white;
  padding: 2rem;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.stat-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.stat-icon {
  font-size: 2rem;
}

.stat-header h3 {
  font-size: 0.95rem;
  color: #6b7280;
  font-weight: 600;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  font-size: 2.25rem;
  font-weight: 700;
  margin: 1rem 0 0.5rem 0;
}

.stat-label {
  color: #9ca3af;
  font-size: 0.875rem;
  font-weight: 500;
}

.stat-income .stat-value {
  color: #10b981;
}

.stat-expense .stat-value {
  color: #ef4444;
}

.stat-balance .stat-value {
  color: #667eea;
}

.stat-month .stat-value {
  color: #f59e0b;
}

.budget-warnings-section {
  margin-bottom: 3rem;
}

.budget-warnings-section h3 {
  color: #333;
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
  font-weight: 600;
}

.warnings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.warning-card {
  display: flex;
  gap: 1rem;
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  border-left: 4px solid;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.warning-card.alert-warning {
  border-left-color: #f59e0b;
  background: #fffbeb;
}

.warning-card.alert-exceeded {
  border-left-color: #ef4444;
  background: #fef2f2;
}

.warning-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.warning-content {
  flex: 1;
}

.warning-content h4 {
  margin: 0 0 0.5rem 0;
  font-size: 1.1rem;
  color: #333;
}

.warning-message {
  margin: 0 0 1rem 0;
  font-size: 0.9rem;
  color: #666;
}

.warning-progress {
  height: 8px;
  background: rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  overflow: hidden;
}

.warning-bar {
  height: 100%;
  transition: width 0.3s ease;
}

.alert-warning .warning-bar {
  background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
}

.alert-exceeded .warning-bar {
  background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
}

.charts-section {
  margin-bottom: 3rem;
}

.chart-card {
  background: white;
  padding: 2rem;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.chart-card h3 {
  color: #333;
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
  font-weight: 600;
}

.dashboard-actions {
  margin-top: 2rem;
}

.action-section h3 {
  color: #333;
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
}

.action-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.action-card {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 2rem;
  background: white;
  border-radius: 16px;
  text-decoration: none;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.action-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.action-icon {
  font-size: 3rem;
  flex-shrink: 0;
}

.action-text h4 {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  color: #333;
}

.action-text p {
  margin: 0;
  color: #666;
  font-size: 0.9rem;
}

.action-card.primary {
  border-left: 5px solid #667eea;
}

.action-card.secondary {
  border-left: 5px solid #10b981;
}

.action-card.tertiary {
  border-left: 5px solid #f59e0b;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }

  .action-grid {
    grid-template-columns: 1fr;
  }
}
</style>