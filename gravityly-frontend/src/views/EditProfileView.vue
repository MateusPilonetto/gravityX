<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const error = ref('');
const successMessage = ref('');
const loading = ref(false);

const form = ref({
  name: '',
  username: '',
  bio: ''
});

onMounted(async () => {
  const token = localStorage.getItem('auth_token');
  try {
    const response = await fetch('http://localhost:8000/api/me', {
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
    });
    
    if (response.ok) {
      const data = await response.json();
      const userData = data.data ? data.data : data;
      form.value.name = userData.name;
      form.value.username = userData.username;
      form.value.bio = userData.bio || '';
    }
  } catch (err) {
    console.error(err);
  }
});

const handleSave = async () => {
  error.value = '';
  successMessage.value = '';
  loading.value = true;
  const token = localStorage.getItem('auth_token');

  try {
    const response = await fetch('http://localhost:8000/api/me', {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(form.value)
    });

    const data = await response.json();

    if (response.ok) {
      successMessage.value = 'Perfil salvo com sucesso!';
      setTimeout(() => router.push('/profile'), 1000);
    } else {
      error.value = data.errors ? Object.values(data.errors)[0][0] : data.message;
    }
  } catch (err) {
    error.value = 'Erro ao conectar ao servidor.';
  } finally {
    loading.value = false;
  }
};

const handleLogout = async () => {
  const token = localStorage.getItem('auth_token');
  await fetch('http://localhost:8000/api/logout', {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
  });
  localStorage.removeItem('auth_token');
  router.push('/login');
};
</script>

<template>
  <div class="edit-container">
    <div class="edit-box">
      <div class="edit-header">
        <router-link to="/profile" class="back-link"><i class="fa-solid fa-chevron-left"></i></router-link>
        <h2>Editar perfil</h2>
        <div style="width: 20px;"></div>
      </div>

      <!-- Simulador de mudança de foto -->
      <div class="change-photo-section">
        <img class="avatar-preview" src="https://images.pexels.com/photos/30372403/pexels-photo-30372403.jpeg?auto=compress&cs=tinysrgb&w=400" />
        <div class="photo-info">
          <h3>{{ form.username }}</h3>
          <button type="button" class="change-photo-btn">Alterar foto do perfil</button>
        </div>
      </div>

      <!-- Formulário -->
      <form @submit.prevent="handleSave" class="form-body">
        <p class="error-msg" v-if="error">{{ error }}</p>
        <p class="success-msg" v-if="successMessage">{{ successMessage }}</p>

        <div class="form-group">
          <label>Nome</label>
          <input type="text" v-model="form.name" required placeholder="Nome completo" />
          <span class="hint">Ajude as pessoas a descobrirem sua conta usando o nome pelo qual você é conhecido.</span>
        </div>
        
        <div class="form-group">
          <label>Nome de usuário</label>
          <input type="text" v-model="form.username" required placeholder="Nome de usuário" />
        </div>
        
        <div class="form-group">
          <label>Biografia</label>
          <!-- Textarea alto para suportar quebras de linha igual ao Instagram -->
          <textarea v-model="form.bio" rows="5" placeholder="Escreva sobre você..."></textarea>
        </div>

        <button type="submit" class="btn-submit" :disabled="loading">
          {{ loading ? 'Salvando...' : 'Enviar' }}
        </button>
      </form>
      
      <!-- Linha separadora -->
      <hr class="divider" />
      
      <!-- Logout -->
      <button @click="handleLogout" class="btn-logout">Sair da conta</button>
    </div>
  </div>
</template>

<style scoped>
.edit-container {
  display: flex;
  justify-content: center;
  padding: 40px 20px 100px 20px;
  color: #fff;
}

.edit-box {
  width: 100%;
  max-width: 600px;
  background-color: transparent;
}

.edit-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.edit-header h2 {
  font-size: 1.2rem;
  font-weight: bold;
  margin: 0;
}

.back-link {
  color: #fff;
  font-size: 1.2rem;
  text-decoration: none;
}

.change-photo-section {
  display: flex;
  align-items: center;
  background-color: #262626;
  padding: 16px;
  border-radius: 12px;
  margin-bottom: 24px;
}

.avatar-preview {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
  margin-right: 15px;
}

.photo-info h3 {
  margin: 0 0 5px 0;
  font-size: 1rem;
}

.change-photo-btn {
  background: none;
  border: none;
  color: #0095f6; /* Azul clássico do Instagram */
  font-weight: bold;
  padding: 0;
  cursor: pointer;
  font-size: 0.9rem;
}

.form-body {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-weight: bold;
  margin-bottom: 8px;
  font-size: 0.95rem;
}

.form-group input,
.form-group textarea {
  background-color: #121212;
  border: 1px solid #363636;
  color: #fff;
  border-radius: 6px;
  padding: 10px 15px;
  font-size: 1rem;
  outline: none;
  transition: border 0.2s;
  resize: vertical;
}

.form-group input:focus,
.form-group textarea:focus {
  border-color: #555;
}

.hint {
  font-size: 0.75rem;
  color: #a8a8a8;
  margin-top: 6px;
}

.btn-submit {
  background-color: #0095f6;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 12px;
  font-weight: bold;
  font-size: 1rem;
  cursor: pointer;
  margin-top: 10px;
}

.btn-submit:disabled {
  opacity: 0.7;
}

.divider {
  border: none;
  border-top: 1px solid #363636;
  margin: 30px 0;
}

.btn-logout {
  width: 100%;
  background: transparent;
  color: #ff5d5d;
  border: 1px solid #ff5d5d;
  border-radius: 8px;
  padding: 12px;
  font-weight: bold;
  font-size: 1rem;
  cursor: pointer;
}

.btn-logout:hover {
  background: rgba(255, 93, 93, 0.1);
}

.error-msg {
  color: #ff5d5d;
  margin: 0;
}

.success-msg {
  color: #4cd964;
  margin: 0;
}
</style>