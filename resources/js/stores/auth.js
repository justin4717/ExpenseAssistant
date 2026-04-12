import { defineStore } from 'pinia';
import api from '../services/api';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('token') || null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    currentUser: (state) => state.user,
  },

  actions: {
    async register(credentials) {
      try {
        const response = await api.post('/register', credentials);
        this.setAuth(response.data.data);
        return response.data;
      } catch (error) {
        throw error.response?.data || error;
      }
    },

    async login(credentials) {
      try {
        const response = await api.post('/login', credentials);
        this.setAuth(response.data.data);
        return response.data;
      } catch (error) {
        throw error.response?.data || error;
      }
    },

    async logout() {
      try {
        await api.post('/logout');
      } catch (error) {
        console.error('Logout error:', error);
      } finally {
        this.clearAuth();
      }
    },

    async fetchUser() {
      try {
        const response = await api.get('/me');
        this.user = response.data.data;
        localStorage.setItem('user', JSON.stringify(this.user));
        return response.data;
      } catch (error) {
        this.clearAuth();
        throw error;
      }
    },

    setAuth(data) {
      this.user = data.user;
      this.token = data.access_token;
      localStorage.setItem('user', JSON.stringify(data.user));
      localStorage.setItem('token', data.access_token);
      
      // Set token in axios headers
      api.defaults.headers.common['Authorization'] = `Bearer ${data.access_token}`;
    },

    clearAuth() {
      this.user = null;
      this.token = null;
      localStorage.removeItem('user');
      localStorage.removeItem('token');
      delete api.defaults.headers.common['Authorization'];
    },

    initAuth() {
      if (this.token) {
        api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
      }
    }
  }
});
