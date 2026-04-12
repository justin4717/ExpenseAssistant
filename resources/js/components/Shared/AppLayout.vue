<template>
  <div class="app-layout">
    <nav class="app-navbar">
      <div class="navbar-container">
        <div class="nav-brand">
          <span class="brand-icon">💰</span>
          <h1>Expense Assistant</h1>
        </div>
        <div class="nav-menu">
          <router-link to="/dashboard" class="nav-link">
            <span class="nav-icon">📊</span>
            <span>Dashboard</span>
          </router-link>
          <router-link to="/expenses" class="nav-link">
            <span class="nav-icon">💳</span>
            <span>Transactions</span>
          </router-link>
          <router-link to="/categories" class="nav-link">
            <span class="nav-icon">🏷️</span>
            <span>Categories</span>
          </router-link>
          <router-link to="/budgets" class="nav-link">
            <span class="nav-icon">💰</span>
            <span>Budgets</span>
          </router-link>
          <router-link to="/balance-sheet" class="nav-link">
            <span class="nav-icon">📊</span>
            <span>Balance Sheet</span>
          </router-link>
          <button @click="handleLogout" class="logout-btn">
            <span class="nav-icon">🚪</span>
            <span>Logout</span>
          </button>
        </div>
      </div>
    </nav>

    <main class="app-main">
      <div class="page-header" v-if="pageTitle">
        <div class="header-content">
          <div class="header-text">
            <h1 class="page-title">
              <span v-if="pageIcon" class="page-icon">{{ pageIcon }}</span>
              {{ pageTitle }}
            </h1>
            <p v-if="pageSubtitle" class="page-subtitle">{{ pageSubtitle }}</p>
          </div>
          <div class="header-actions-wrapper" v-if="$slots['header-actions']">
            <slot name="header-actions"></slot>
          </div>
        </div>
      </div>
      
      <div class="page-content">
        <slot></slot>
      </div>
    </main>

    <footer class="app-footer">
      <div class="footer-content">
        <p>&copy; 2025 Expense Assistant. Track your finances with ease.</p>
      </div>
    </footer>
  </div>
</template>

<script>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

export default {
  name: 'AppLayout',
  props: {
    pageTitle: {
      type: String,
      default: ''
    },
    pageSubtitle: {
      type: String,
      default: ''
    },
    pageIcon: {
      type: String,
      default: ''
    }
  },
  setup() {
    const router = useRouter();
    const authStore = useAuthStore();

    const handleLogout = async () => {
      await authStore.logout();
      router.push('/login');
    };

    return {
      handleLogout
    };
  }
};
</script>

<style scoped>
.app-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

/* Navbar Styles */
.app-navbar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.navbar-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.brand-icon {
  font-size: 2rem;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.nav-brand h1 {
  color: white;
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  letter-spacing: -0.5px;
}

.nav-menu {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: rgba(255, 255, 255, 0.9);
  text-decoration: none;
  padding: 0.75rem 1.25rem;
  border-radius: 12px;
  transition: all 0.3s ease;
  font-weight: 500;
  font-size: 0.95rem;
}

.nav-link:hover {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  transform: translateY(-2px);
}

.nav-link.router-link-active {
  background: rgba(255, 255, 255, 0.25);
  color: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.nav-icon {
  font-size: 1.2rem;
}

.logout-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: none;
  padding: 0.75rem 1.25rem;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  margin-left: 0.5rem;
}

.logout-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Main Content */
.app-main {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.page-header {
  background: white;
  border-bottom: 1px solid #e5e7eb;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.25rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.header-text {
  flex: 1;
}

.header-actions-wrapper {
  flex-shrink: 0;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.page-icon {
  font-size: 1.75rem;
}

.page-subtitle {
  color: #6b7280;
  font-size: 0.95rem;
  margin: 0.35rem 0 0 0;
}

.page-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
  width: 100%;
  flex: 1;
}

/* Footer */
.app-footer {
  background: white;
  border-top: 1px solid #e5e7eb;
  margin-top: auto;
}

.footer-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
  text-align: center;
}

.footer-content p {
  color: #6b7280;
  margin: 0;
  font-size: 0.9rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .navbar-container {
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
  }

  .nav-menu {
    flex-direction: column;
    width: 100%;
  }

  .nav-link, .logout-btn {
    width: 100%;
    justify-content: center;
  }

  .page-title {
    font-size: 1.5rem;
  }

  .page-icon {
    font-size: 2rem;
  }

  .page-content {
    padding: 1rem;
  }
}
</style>
