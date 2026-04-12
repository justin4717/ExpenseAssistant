<template>
  <nav class="navbar">
    <div class="nav-container">
      <div class="nav-brand">
        <h1>💰 Expense Assistant</h1>
      </div>
      <div class="nav-menu">
        <router-link to="/dashboard" class="nav-link">Dashboard</router-link>
        <router-link to="/expenses" class="nav-link">Transactions</router-link>
        <router-link to="/categories" class="nav-link">Categories</router-link>
        <button @click="handleLogout" class="logout-btn">Logout</button>
      </div>
    </div>
  </nav>
</template>

<script>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

export default {
  name: 'Navbar',
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
.navbar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.nav-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.nav-brand h1 {
  margin: 0;
  color: white;
  font-size: 1.5rem;
  font-weight: 700;
}

.nav-menu {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.nav-link {
  color: rgba(255, 255, 255, 0.9);
  text-decoration: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  transition: all 0.3s;
  font-weight: 500;
}

.nav-link:hover,
.nav-link.router-link-active {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.logout-btn {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 0.5rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
  font-weight: 500;
}

.logout-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-1px);
}
</style>
