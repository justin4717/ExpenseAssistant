<template>
  <AppLayout page-title="Balance Sheet" page-icon="📊" page-subtitle="Your financial position and performance">
    <div class="balance-sheet-content">
      <!-- Period Selector -->
      <div class="period-selector">
        <select v-model="selectedPeriod" @change="calculateBalanceSheet">
          <option value="all">All Time</option>
          <option value="year">This Year</option>
          <option value="month">This Month</option>
          <option value="quarter">This Quarter</option>
          <option value="custom">Custom Range</option>
        </select>
        
        <div v-if="selectedPeriod === 'custom'" class="custom-date-range">
          <input v-model="customStartDate" type="date" @change="calculateBalanceSheet" />
          <span>to</span>
          <input v-model="customEndDate" type="date" @change="calculateBalanceSheet" />
        </div>
      </div>

      <!-- Net Worth Summary -->
      <div class="net-worth-section">
        <div class="net-worth-card">
          <div class="net-worth-header">
            <h2>Net Worth</h2>
            <span class="period-label">{{ periodLabel }}</span>
          </div>
          <div class="net-worth-amount" :class="netWorth >= 0 ? 'positive' : 'negative'">
            {{ formatCurrency(netWorth) }}
          </div>
          <div class="net-worth-breakdown">
            <div class="breakdown-item">
              <span class="label">Total Income</span>
              <span class="value income">{{ formatCurrency(totalIncome) }}</span>
            </div>
            <div class="breakdown-item">
              <span class="label">Total Expenses</span>
              <span class="value expense">{{ formatCurrency(totalExpenses) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Financial Statements Grid -->
      <div class="statements-grid">
        <!-- Income Statement -->
        <div class="statement-card">
          <h3>📈 Income Statement</h3>
          <div class="statement-section">
            <div class="section-header">Revenue</div>
            <div v-for="item in incomeByCategory" :key="item.category.id" class="line-item">
              <span class="item-label">
                {{ item.category.icon }} {{ item.category.name }}
              </span>
              <span class="item-value income">{{ formatCurrency(item.amount) }}</span>
            </div>
            <div class="line-item total">
              <span class="item-label">Total Income</span>
              <span class="item-value">{{ formatCurrency(totalIncome) }}</span>
            </div>
          </div>

          <div class="statement-section">
            <div class="section-header">Expenses</div>
            <div v-for="item in expensesByCategory" :key="item.category.id" class="line-item">
              <span class="item-label">
                {{ item.category.icon }} {{ item.category.name }}
              </span>
              <span class="item-value expense">{{ formatCurrency(item.amount) }}</span>
            </div>
            <div class="line-item total">
              <span class="item-label">Total Expenses</span>
              <span class="item-value">{{ formatCurrency(totalExpenses) }}</span>
            </div>
          </div>

          <div class="statement-section highlight">
            <div class="line-item net-income">
              <span class="item-label">Net Income</span>
              <span class="item-value" :class="netWorth >= 0 ? 'profit' : 'loss'">
                {{ formatCurrency(netWorth) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Cash Flow Analysis -->
        <div class="statement-card">
          <h3>💰 Cash Flow Analysis</h3>
          
          <div class="cash-flow-summary">
            <div class="flow-item">
              <div class="flow-label">Cash Inflow</div>
              <div class="flow-value positive">{{ formatCurrency(totalIncome) }}</div>
              <div class="flow-bar">
                <div class="bar-fill positive" :style="{ width: '100%' }"></div>
              </div>
            </div>

            <div class="flow-item">
              <div class="flow-label">Cash Outflow</div>
              <div class="flow-value negative">{{ formatCurrency(totalExpenses) }}</div>
              <div class="flow-bar">
                <div class="bar-fill negative" :style="{ width: calculatePercentage(totalExpenses, totalIncome) + '%' }"></div>
              </div>
            </div>

            <div class="flow-item">
              <div class="flow-label">Net Cash Flow</div>
              <div class="flow-value" :class="netWorth >= 0 ? 'positive' : 'negative'">
                {{ formatCurrency(netWorth) }}
              </div>
              <div class="flow-bar">
                <div class="bar-fill" :class="netWorth >= 0 ? 'positive' : 'negative'" 
                     :style="{ width: calculatePercentage(Math.abs(netWorth), totalIncome) + '%' }">
                </div>
              </div>
            </div>
          </div>

          <div class="statement-section">
            <div class="section-header">Payment Methods Breakdown</div>
            <div v-for="method in paymentMethodBreakdown" :key="method.method" class="line-item">
              <span class="item-label">{{ getPaymentIcon(method.method) }} {{ formatPaymentMethod(method.method) }}</span>
              <span class="item-value">{{ formatCurrency(method.amount) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Key Metrics -->
      <div class="metrics-section">
        <h3>📊 Key Financial Metrics</h3>
        <div class="metrics-grid">
          <div class="metric-card">
            <div class="metric-icon">💵</div>
            <div class="metric-content">
              <div class="metric-label">Average Daily Spending</div>
              <div class="metric-value">{{ formatCurrency(averageDailySpending) }}</div>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-icon">📅</div>
            <div class="metric-content">
              <div class="metric-label">Average Transaction</div>
              <div class="metric-value">{{ formatCurrency(averageTransaction) }}</div>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-icon">🎯</div>
            <div class="metric-content">
              <div class="metric-label">Savings Rate</div>
              <div class="metric-value">{{ savingsRate }}%</div>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-icon">🔢</div>
            <div class="metric-content">
              <div class="metric-label">Total Transactions</div>
              <div class="metric-value">{{ totalTransactions }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Monthly Trend -->
      <div class="chart-section">
        <h3>📈 Monthly Cash Flow Trend</h3>
        <div class="chart-card">
          <Line v-if="trendChartData" :data="trendChartData" :options="chartOptions" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '../Shared/AppLayout.vue';
import api from '../../services/api';
import { Line } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

export default {
  name: 'BalanceSheet',
  components: {
    AppLayout,
    Line
  },
  setup() {
    const expenses = ref([]);
    const categories = ref([]);
    const selectedPeriod = ref('all');
    const customStartDate = ref('');
    const customEndDate = ref('');

    const totalIncome = ref(0);
    const totalExpenses = ref(0);
    const netWorth = ref(0);
    const incomeByCategory = ref([]);
    const expensesByCategory = ref([]);
    const paymentMethodBreakdown = ref([]);
    const trendChartData = ref(null);
    const totalTransactions = ref(0);
    const averageDailySpending = ref(0);
    const averageTransaction = ref(0);
    const savingsRate = ref(0);

    const periodLabel = computed(() => {
      const labels = {
        'all': 'All Time',
        'year': 'This Year',
        'month': 'This Month',
        'quarter': 'This Quarter',
        'custom': 'Custom Range'
      };
      return labels[selectedPeriod.value] || 'All Time';
    });

    const chartOptions = {
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: 2.5,
      plugins: {
        legend: {
          position: 'bottom',
        },
        tooltip: {
          mode: 'index',
          intersect: false,
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

    const formatPaymentMethod = (method) => {
      const methods = {
        'cash': 'Cash',
        'card': 'Card',
        'bank_transfer': 'Bank Transfer',
        'digital_wallet': 'Digital Wallet',
        'other': 'Other'
      };
      return methods[method] || method;
    };

    const getPaymentIcon = (method) => {
      const icons = {
        'cash': '💵',
        'card': '💳',
        'bank_transfer': '🏦',
        'digital_wallet': '📱',
        'other': '💰'
      };
      return icons[method] || '💰';
    };

    const calculatePercentage = (value, total) => {
      if (total === 0) return 0;
      return Math.min((value / total) * 100, 100);
    };

    const getFilteredExpenses = () => {
      let filtered = [...expenses.value];
      const now = new Date();

      switch (selectedPeriod.value) {
        case 'year':
          filtered = filtered.filter(e => {
            const date = new Date(e.expense_date);
            return date.getFullYear() === now.getFullYear();
          });
          break;
        case 'month':
          filtered = filtered.filter(e => {
            const date = new Date(e.expense_date);
            return date.getMonth() === now.getMonth() && 
                   date.getFullYear() === now.getFullYear();
          });
          break;
        case 'quarter':
          const quarter = Math.floor(now.getMonth() / 3);
          filtered = filtered.filter(e => {
            const date = new Date(e.expense_date);
            const expenseQuarter = Math.floor(date.getMonth() / 3);
            return expenseQuarter === quarter && 
                   date.getFullYear() === now.getFullYear();
          });
          break;
        case 'custom':
          if (customStartDate.value && customEndDate.value) {
            filtered = filtered.filter(e => {
              const date = new Date(e.expense_date);
              return date >= new Date(customStartDate.value) && 
                     date <= new Date(customEndDate.value);
            });
          }
          break;
      }

      return filtered;
    };

    const calculateBalanceSheet = () => {
      const filtered = getFilteredExpenses();
      
      let income = 0;
      let expense = 0;
      const incomeCat = {};
      const expenseCat = {};
      const paymentMethods = {};
      const monthlyData = {};

      filtered.forEach(item => {
        const amount = parseFloat(item.amount);
        const category = item.category;
        
        if (category && category.type === 'income') {
          income += amount;
          if (!incomeCat[category.id]) {
            incomeCat[category.id] = { category, amount: 0 };
          }
          incomeCat[category.id].amount += amount;
        } else {
          expense += amount;
          if (category) {
            if (!expenseCat[category.id]) {
              expenseCat[category.id] = { category, amount: 0 };
            }
            expenseCat[category.id].amount += amount;
          }
        }

        // Payment methods
        const method = item.payment_method || 'other';
        if (!paymentMethods[method]) {
          paymentMethods[method] = 0;
        }
        paymentMethods[method] += amount;

        // Monthly trend
        const date = new Date(item.expense_date);
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

      totalIncome.value = income;
      totalExpenses.value = expense;
      netWorth.value = income - expense;
      totalTransactions.value = filtered.length;

      // Calculate metrics
      if (filtered.length > 0) {
        const dates = filtered.map(e => new Date(e.expense_date));
        const minDate = new Date(Math.min(...dates));
        const maxDate = new Date(Math.max(...dates));
        const days = Math.max(1, Math.ceil((maxDate - minDate) / (1000 * 60 * 60 * 24)));
        
        averageDailySpending.value = expense / days;
        averageTransaction.value = (income + expense) / filtered.length;
      } else {
        averageDailySpending.value = 0;
        averageTransaction.value = 0;
      }

      savingsRate.value = income > 0 ? Math.round(((income - expense) / income) * 100) : 0;

      // Sort and assign
      incomeByCategory.value = Object.values(incomeCat).sort((a, b) => b.amount - a.amount);
      expensesByCategory.value = Object.values(expenseCat).sort((a, b) => b.amount - a.amount);
      
      paymentMethodBreakdown.value = Object.entries(paymentMethods)
        .map(([method, amount]) => ({ method, amount }))
        .sort((a, b) => b.amount - a.amount);

      // Prepare chart data
      const months = Object.keys(monthlyData).slice(-6);
      const incomeData = months.map(month => monthlyData[month].income);
      const expenseData = months.map(month => monthlyData[month].expense);
      const netData = months.map(month => monthlyData[month].income - monthlyData[month].expense);

      trendChartData.value = {
        labels: months,
        datasets: [
          {
            label: 'Income',
            data: incomeData,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4
          },
          {
            label: 'Expenses',
            data: expenseData,
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            fill: true,
            tension: 0.4
          },
          {
            label: 'Net',
            data: netData,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            fill: true,
            tension: 0.4
          }
        ]
      };
    };

    const fetchData = async () => {
      try {
        const [expensesRes, categoriesRes] = await Promise.all([
          api.get('/expenses'),
          api.get('/categories')
        ]);

        expenses.value = expensesRes.data.data;
        categories.value = categoriesRes.data.data;
        
        calculateBalanceSheet();
      } catch (error) {
        console.error('Error fetching data:', error);
      }
    };

    onMounted(() => {
      fetchData();
    });

    return {
      selectedPeriod,
      customStartDate,
      customEndDate,
      periodLabel,
      totalIncome,
      totalExpenses,
      netWorth,
      incomeByCategory,
      expensesByCategory,
      paymentMethodBreakdown,
      trendChartData,
      chartOptions,
      totalTransactions,
      averageDailySpending,
      averageTransaction,
      savingsRate,
      formatCurrency,
      formatPaymentMethod,
      getPaymentIcon,
      calculatePercentage,
      calculateBalanceSheet
    };
  }
};
</script>

<style scoped>
.balance-sheet-content {
  width: 100%;
}

.period-selector {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.period-selector select {
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  min-width: 200px;
  cursor: pointer;
}

.custom-date-range {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.custom-date-range input {
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
}

.net-worth-section {
  margin-bottom: 2rem;
}

.net-worth-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2.5rem;
  border-radius: 16px;
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
  color: white;
}

.net-worth-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.net-worth-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
}

.period-label {
  padding: 0.5rem 1rem;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 20px;
  font-size: 0.9rem;
}

.net-worth-amount {
  font-size: 3rem;
  font-weight: 700;
  margin: 1rem 0;
}

.net-worth-amount.positive {
  color: #10b981;
}

.net-worth-amount.negative {
  color: #ef4444;
}

.net-worth-breakdown {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.breakdown-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.breakdown-item .label {
  font-size: 0.9rem;
  opacity: 0.9;
}

.breakdown-item .value {
  font-size: 1.5rem;
  font-weight: 600;
}

.statements-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

.statement-card {
  background: white;
  padding: 2rem;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.statement-card h3 {
  margin: 0 0 1.5rem 0;
  font-size: 1.25rem;
  color: #333;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f3f4f6;
}

.statement-section {
  margin-bottom: 2rem;
}

.statement-section.highlight {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 0;
}

.section-header {
  font-weight: 600;
  color: #6b7280;
  margin-bottom: 1rem;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.line-item {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f3f4f6;
}

.line-item.total {
  font-weight: 600;
  border-top: 2px solid #e5e7eb;
  border-bottom: none;
  margin-top: 0.5rem;
  padding-top: 1rem;
}

.line-item.net-income {
  font-size: 1.1rem;
  font-weight: 700;
  border: none;
}

.item-label {
  color: #374151;
}

.item-value {
  font-weight: 600;
}

.item-value.income {
  color: #10b981;
}

.item-value.expense {
  color: #ef4444;
}

.item-value.profit {
  color: #10b981;
  font-size: 1.2rem;
}

.item-value.loss {
  color: #ef4444;
  font-size: 1.2rem;
}

.cash-flow-summary {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.flow-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.flow-label {
  font-size: 0.9rem;
  color: #6b7280;
  font-weight: 600;
}

.flow-value {
  font-size: 1.25rem;
  font-weight: 700;
}

.flow-value.positive {
  color: #10b981;
}

.flow-value.negative {
  color: #ef4444;
}

.flow-bar {
  height: 8px;
  background: #f3f4f6;
  border-radius: 4px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  transition: width 0.3s ease;
}

.bar-fill.positive {
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

.bar-fill.negative {
  background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
}

.metrics-section {
  margin-bottom: 2rem;
}

.metrics-section h3 {
  margin: 0 0 1.5rem 0;
  font-size: 1.25rem;
  color: #333;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.metric-card {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.metric-icon {
  font-size: 2.5rem;
  flex-shrink: 0;
}

.metric-content {
  flex: 1;
}

.metric-label {
  font-size: 0.85rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.metric-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #333;
}

.chart-section {
  margin-bottom: 2rem;
}

.chart-section h3 {
  margin: 0 0 1.5rem 0;
  font-size: 1.25rem;
  color: #333;
}

.chart-card {
  background: white;
  padding: 2rem;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

@media (max-width: 768px) {
  .statements-grid {
    grid-template-columns: 1fr;
  }

  .net-worth-amount {
    font-size: 2rem;
  }

  .period-selector {
    flex-direction: column;
    align-items: stretch;
  }

  .custom-date-range {
    flex-direction: column;
  }
}
</style>
