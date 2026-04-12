<template>
  <AppLayout page-title="Categories" page-icon="🏷️" page-subtitle="Organize your transactions with custom categories">
    <div class="categories-content">
      <div class="content-header">
        <button @click="showCreateForm = true" class="btn-primary">
          <span class="btn-icon">➕</span>
          Add Category
        </button>
      </div>

      <!-- Create/Edit Form -->
      <div v-if="showCreateForm || editingCategory" class="form-card">
        <h3>{{ editingCategory ? 'Edit Category' : 'New Category' }}</h3>
        
        <form @submit.prevent="editingCategory ? updateCategory() : createCategory()">
          <div class="form-row">
            <div class="form-group">
              <label>Name</label>
              <input v-model="form.name" type="text" required placeholder="e.g., Food & Dining" />
            </div>

            <div class="form-group">
              <label>Type</label>
              <select v-model="form.type" required>
                <option value="expense">Expense</option>
                <option value="income">Income</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Color</label>
              <input v-model="form.color" type="color" />
            </div>

            <div class="form-group">
              <label>Icon - Click to select</label>
              <div class="icon-input-wrapper" ref="emojiPickerWrapper">
                <input 
                  v-model="form.icon" 
                  type="text" 
                  placeholder="Click to select emoji"
                  @click="showEmojiPicker = true"
                  readonly
                  class="icon-input"
                />
                <div v-if="showEmojiPicker" class="emoji-picker">
                  <div 
                    v-for="emoji in emojiList" 
                    :key="emoji"
                    @click="selectEmoji(emoji)"
                    class="emoji-option"
                    :class="{ selected: form.icon === emoji }"
                  >
                    {{ emoji }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-primary">
              {{ editingCategory ? 'Update' : 'Create' }}
            </button>
            <button type="button" @click="cancelForm" class="btn-secondary">
              Cancel
            </button>
          </div>
        </form>
      </div>

      <!-- Categories Grid -->
      <div v-if="loading" class="loading">Loading categories...</div>
      
      <div v-else-if="categories.length === 0" class="empty-state">
        <p>📭 No categories yet</p>
        <p>Create your first category to start organizing expenses</p>
      </div>

      <div v-else class="categories-grid">
        <div 
          v-for="category in categories" 
          :key="category.id"
          class="category-card"
          :style="{ borderLeftColor: category.color }"
        >
          <div class="category-header">
            <div class="category-icon">{{ category.icon }}</div>
            <div class="category-info">
              <h4>{{ category.name }}</h4>
              <span class="category-type" :class="category.type">
                {{ category.type }}
              </span>
            </div>
          </div>

          <div class="category-actions">
            <button @click="editCategory(category)" class="btn-icon" title="Edit">
              ✏️
            </button>
            <button @click="deleteCategory(category.id)" class="btn-icon danger" title="Delete">
              🗑️
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import api from '../../services/api';
import AppLayout from '../Shared/AppLayout.vue';

export default {
  name: 'CategoryList',
  components: {
    AppLayout
  },
  setup() {
    const router = useRouter();
    const authStore = useAuthStore();
    
    const categories = ref([]);
    const loading = ref(false);
    const showCreateForm = ref(false);
    const editingCategory = ref(null);
    const showEmojiPicker = ref(false);
    const emojiPickerWrapper = ref(null);
    
    const form = ref({
      name: '',
      type: 'expense',
      color: '#667eea',
      icon: '🏷️'
    });

    const emojiList = [
      // Food & Dining
      '🍕', '🍔', '🍟', '🌭', '🍿', '🥗', '🍝', '🍜', '🍱', '☕', '🍺', '🍷', 
      '🍰', '🎂', '🍪', '🍩', '🥐', '🍞', '🥖', '🧀', '🥚', '🍳', '🥓', '🥩',
      '🍗', '🍖', '🌮', '🌯', '🥙', '🥪', '🍣', '🍤', '🦐', '🦞', '🍛', '🍲',
      '🥘', '🍢', '🥟', '🍡', '🥠', '🧁', '🍦', '🍧', '🥤', '🧃', '🧋', '🍹',
      // Transportation
      '🚗', '⛽', '🚌', '🚕', '🚙', '🏍️', '🚲', '✈️', '🚂', '🚆', '🚇', '🚊',
      '🚐', '🚑', '🚒', '🚓', '🚜', '🛵', '🛴', '🚁', '🛩️', '🚀', '🛸', '🚢',
      // Shopping & Retail
      '🛒', '👕', '👗', '👠', '💄', '📱', '💻', '🎮', '📚', '🏠', '🛍️', '👔',
      '👖', '👘', '👚', '🧥', '🧦', '👜', '👝', '🎒', '👓', '🕶️', '💍', '👑',
      '⌚', '📷', '📹', '🖥️', '⌨️', '🖱️', '🖨️', '💿', '📀', '🎧', '📻', '🎙️',
      // Bills & Utilities
      '⚡', '💧', '📡', '🔥', '💳', '🏦', '🧾', '📄', '📃', '🧮', '💰', '💸',
      '📊', '📈', '📉', '🏧', '💱', '💲', '💶', '💷', '💴', '💵',
      // Health & Fitness
      '🏥', '💊', '🏋️', '🧘', '⚽', '🏃', '💉', '🩺', '🩹', '🧬', '🦷', '🧠',
      '🏃‍♀️', '🚴', '🚴‍♀️', '🏊', '🏊‍♀️', '⛹️', '🤸', '🧗', '🤾', '🏌️', '🎾', '🏓',
      '🏸', '🥊', '🥋', '🥅', '⛸️', '🛹', '🛼', '🎿', '🏂',
      // Income & Salary
      '💰', '💵', '💸', '💳', '💼', '📈', '💹', '🏆', '🥇', '🎖️', '🎁', '🎉',
      // Entertainment & Leisure
      '🎬', '🎵', '🎮', '🎨', '🎭', '🎪', '🎤', '🎧', '🎼', '🎹', '🥁', '🎸',
      '🎺', '🎷', '🎻', '🎲', '🎯', '🎳', '🎰', '🃏', '🎴', '🀄', '🧩', '🎱',
      '🎟️', '🎫', '🎗️', '🏟️', '🎡', '🎢', '🎠', '🎪', '🎨', '🖼️', '🎬', '📺',
      // Education & Work
      '📝', '✏️', '📚', '📖', '📕', '📗', '📘', '📙', '📓', '📔', '📒', '📄',
      '📃', '📋', '📊', '📈', '📉', '🗂️', '📁', '📂', '🗃️', '🗄️', '📇', '🗓️',
      '📅', '📆', '🗒️', '🗓️', '💼', '📎', '🖇️', '✂️', '📐', '📏', '🧷', '🖊️',
      // Home & Garden
      '🏠', '🏡', '🏘️', '🏚️', '🏗️', '🏭', '🏢', '🏬', '🏣', '🏤', '🏥', '🏦',
      '🛋️', '🪑', '🚪', '🛏️', '🛁', '🚿', '🚽', '🧻', '🧹', '🧺', '🧼', '🪣',
      '🧽', '🧴', '🌱', '🌿', '🌾', '🌳', '🌲', '🌴', '🪴', '🌵', '🌻', '🌷',
      // Pets & Animals
      '🐕', '🐶', '🐩', '🐈', '🐱', '🐁', '🐀', '🐹', '🐰', '🐇', '🐿️', '🦔',
      '🐾', '🦴', '🐟', '🐠', '🐡', '🐢', '🦎', '🐍', '🦜', '🐦', '🦆', '🦢',
      // Travel & Places
      '✈️', '🗺️', '🧳', '🎒', '🏖️', '🏝️', '🏔️', '⛰️', '🏕️', '🏞️', '🗻', '🏛️',
      '🕌', '🕍', '⛪', '🗼', '🗽', '🎡', '🎢', '🎠', '⛲', '⛱️', '🏖️', '🌅',
      // Gifts & Celebrations
      '🎁', '🎀', '🎊', '🎉', '🎈', '🎂', '🎄', '🎃', '🎇', '🎆', '🧨', '🎋',
      '🎍', '🎎', '🎏', '🎐', '🎑', '🧧', '🎗️', '🎟️', '🎫', '💝', '💖', '💗',
      // Other Common
      '⭐', '✨', '💎', '🔑', '🔒', '🔓', '🎯', '📌', '📍', '🚩', '🏁', '🎌',
      '🏳️', '🏴', '🏷️', '❤️', '🧡', '💛', '💚', '💙', '💜', '🤍', '🖤', '🤎'
    ];

    const fetchCategories = async () => {
      loading.value = true;
      try {
        const response = await api.get('/categories');
        categories.value = response.data.data;
      } catch (error) {
        alert('Failed to load categories');
      } finally {
        loading.value = false;
      }
    };

    const createCategory = async () => {
      try {
        await api.post('/categories', form.value);
        showCreateForm.value = false;
        resetForm();
        fetchCategories();
      } catch (error) {
        alert(error.response?.data?.message || 'Failed to create category');
      }
    };

    const editCategory = (category) => {
      editingCategory.value = category;
      form.value = {
        name: category.name,
        type: category.type,
        color: category.color || '#667eea',
        icon: category.icon || '🏷️'
      };
      showCreateForm.value = false;
    };

    const updateCategory = async () => {
      try {
        await api.put(`/categories/${editingCategory.value.id}`, form.value);
        editingCategory.value = null;
        resetForm();
        fetchCategories();
      } catch (error) {
        alert('Failed to update category');
      }
    };

    const deleteCategory = async (id) => {
      if (!confirm('Are you sure you want to delete this category?')) return;
      
      try {
        await api.delete(`/categories/${id}`);
        fetchCategories();
      } catch (error) {
        alert('Failed to delete category');
      }
    };

    const cancelForm = () => {
      showCreateForm.value = false;
      editingCategory.value = null;
      showEmojiPicker.value = false;
      resetForm();
    };

    const selectEmoji = (emoji) => {
      form.value.icon = emoji;
      showEmojiPicker.value = false;
    };

    const handleClickOutside = (event) => {
      if (emojiPickerWrapper.value && !emojiPickerWrapper.value.contains(event.target)) {
        showEmojiPicker.value = false;
      }
    };

    const resetForm = () => {
      form.value = {
        name: '',
        type: 'expense',
        color: '#667eea',
        icon: '🏷️'
      };
    };

    onMounted(() => {
      fetchCategories();
      document.addEventListener('click', handleClickOutside);
    });

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside);
    });

    return {
      categories,
      loading,
      showCreateForm,
      editingCategory,
      form,
      emojiList,
      showEmojiPicker,
      emojiPickerWrapper,
      createCategory,
      editCategory,
      updateCategory,
      deleteCategory,
      cancelForm,
      selectEmoji
    };
  }
};
</script>

<style scoped>
.categories-content {
  width: 100%;
}

.content-header {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 2rem;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
  color: white;
  border: none;
  padding: 0.875rem 1.75rem;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(255, 107, 107, 0.4);
}

.btn-icon {
  font-size: 1.2rem;
}

.form-card {
  background: white;
  padding: 2rem;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.form-card h3 {
  margin-top: 0;
  color: #333;
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
}

.form-group label {
  margin-bottom: 0.5rem;
  color: #555;
  font-weight: 500;
}

.form-group input,
.form-group select {
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 5px;
  font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #ff6b6b;
  box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.loading {
  text-align: center;
  padding: 3rem;
  color: #666;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  background: white;
  border-radius: 10px;
}

.empty-state p:first-child {
  font-size: 48px;
  margin-bottom: 1rem;
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.category-card {
  background: white;
  padding: 1.5rem;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  border-left: 4px solid;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.category-header {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.category-icon {
  font-size: 32px;
}

.category-info h4 {
  margin: 0 0 0.5rem 0;
  color: #333;
}

.category-type {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.category-type.expense {
  background: #ffebee;
  color: #c62828;
}

.category-type.income {
  background: #e8f5e9;
  color: #2e7d32;
}

.category-actions {
  display: flex;
  gap: 0.5rem;
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

.icon-input-wrapper {
  position: relative;
  width: 100%;
}

.icon-input {
  width: 100%;
  height: 50px;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 20px;
  text-align: center;
  cursor: pointer;
  background: white;
  box-sizing: border-box;
}

.icon-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.emoji-picker {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  gap: 8px;
  padding: 12px;
  background: #f9f9f9;
  border-radius: 8px;
  margin-top: 8px;
  height: 200px;
  overflow-y: auto;
  border: 1px solid #ddd;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  animation: slideDown 0.2s ease-out;
  z-index: 10;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.emoji-option {
  font-size: 24px;
  cursor: pointer;
  text-align: center;
  padding: 8px;
  border-radius: 6px;
  transition: all 0.2s;
  height: 40px;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.emoji-option:hover {
  background: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.emoji-option.selected {
  background: #667eea;
}
</style>
