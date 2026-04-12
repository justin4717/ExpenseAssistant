<template>
  <div class="page-header">
    <h1>{{ icon }} {{ title }}</h1>
    <slot name="actions"></slot>
  </div>
</template>

<script>
export default {
  name: 'PageHeader',
  props: {
    title: {
      type: String,
      required: true
    },
    icon: {
      type: String,
      default: ''
    }
  }
};
</script>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #e0e0e0;
}

.page-header h1 {
  font-size: 2rem;
  color: #333;
  margin: 0;
  font-weight: 700;
}
</style>
