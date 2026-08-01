<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const error = ref('');
const success = ref('');

const form = ref({
  name: '',
  username: '',
  bio: ''
});

// Busca os dados atuais para preencher o formulário
onMounted(async () => {
  const token = localStorage.getItem('auth_token');
  const response = await fetch('http://localhost:8000/api/me', {
    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
  });
  if (response.ok) {
    const data = await response.json();
    form.value.name = data.data.name;
    form.value.username = data.data.username;
    form.value.bio = data.data.bio || '';
  }
});

const handleSave = async () => {
  error.value = '';
  success.value = '';
  const token = localStorage.getItem('auth_token');

  try {
    const response = await fetch('http://localhost:8000/api/me', {
      method: 'PUT', // PUT porque estamos atualizando dados
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(form.value)
    });

    const data = await response.json();

    if (response.ok) {
      router.push('/profile'); // Volta pro perfil após salvar
    } else {
      error.value = data.errors ? Object.values(data.errors)[0][0] : data.message;
    }
  } catch (err) {
    error.value = 'Failed to connect to the server.';
  }
};
</script>

<template>
  <div class="edit-screen">
    <div class="header">
      <router-link to="/profile" class="back-btn"><i class="fa-solid fa-chevron-left"></i> Back</router-link>
      <h2>Edit Profile</h2>
      <div style="width: 50px;"></div> <!-- Placeholder para centralizar -->
    </div>

    <div class="edit-box glass-effect">
      <p class="error" v-if="error">{{ error }}</p>
      
      <form @submit.prevent="handleSave" class="edit-form">
        <div class="input-group">
          <label>Name</label>
          <input type="text" v-model="form.name" required class="input-field" />
        </div>
        
        <div class="input-group">
          <label>Username</label>
          <input type="text" v-model="form.username" required class="input-field" />
        </div>
        
        <div class="input-group">
          <label>Bio</label>
          <textarea v-model="form.bio" class="input-field" rows="4" placeholder="Tell us about yourself..."></textarea>
        </div>
        
        <button type="submit" class="btn">Save Changes</button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.edit-screen { padding: 1rem; color: white; }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.header h2 { margin: 0; font-size: 1.5rem; }
.back-btn { color: #6F5CFF; text-decoration: none; font-weight: bold; }
.edit-box { padding: 2rem; border-radius: 1.5rem; }
.edit-form { display: flex; flex-direction: column; gap: 1.2rem; }
.input-group label { display: block; margin-bottom: 0.5rem; color: #C9C2E8; font-size: 0.9rem; }
.input-field { width: 100%; padding: 0.8rem 1.2rem; border-radius: 1rem; border: 1px solid rgba(111, 92, 255, 0.3); background: rgba(33, 25, 52, 0.6); color: white; font-size: 1rem; box-sizing: border-box; outline: none; transition: border-color 0.3s ease; resize: vertical; }
.input-field:focus { border-color: #6F5CFF; }
.btn { background-color: #6F5CFF; color: white; border: none; padding: 1rem; border-radius: 1rem; font-weight: bold; font-size: 1.1rem; cursor: pointer; transition: all 0.3s ease; margin-top: 1rem; }
.btn:hover { background-color: #C9C2E8; color: #211934; transform: translateY(-2px); }
.error { color: #ff5d5d; text-align: center; }
</style>