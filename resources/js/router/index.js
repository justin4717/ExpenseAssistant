import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

// Lazy load components for better performance
const Login = () => import('../components/Auth/Login.vue');
const Register = () => import('../components/Auth/Register.vue');
const Dashboard = () => import('../components/Dashboard.vue');
const ExpenseList = () => import('../components/Expenses/ExpenseList.vue');
const ExpenseCreate = () => import('../components/Expenses/ExpenseCreate.vue');
const CategoryList = () => import('../components/Categories/CategoryList.vue');
const BudgetList = () => import('../components/Budgets/BudgetList.vue');
const BalanceSheet = () => import('../components/Reports/BalanceSheet.vue');

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { guest: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { guest: true }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/expenses',
    name: 'ExpenseList',
    component: ExpenseList,
    meta: { requiresAuth: true }
  },
  {
    path: '/expenses/create',
    name: 'ExpenseCreate',
    component: ExpenseCreate,
    meta: { requiresAuth: true }
  },
  {
    path: '/categories',
    name: 'CategoryList',
    component: CategoryList,
    meta: { requiresAuth: true }
  },
  {
    path: '/budgets',
    name: 'BudgetList',
    component: BudgetList,
    meta: { requiresAuth: true }
  },
  {
    path: '/balance-sheet',
    name: 'BalanceSheet',
    component: BalanceSheet,
    meta: { requiresAuth: true }
  }
];

const router = createRouter({
  history: createWebHistory('/expense-assistant/'),
  routes
});

// Navigation guard
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login');
  } else if (to.meta.guest && authStore.isAuthenticated) {
    next('/dashboard');
  } else {
    next();
  }
});

export default router;
