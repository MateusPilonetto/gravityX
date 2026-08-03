<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api, getToken } from '../services/api';

const route = useRoute();
const router = useRouter();

// Variáveis de Estado
const profileUser = ref(null); // O dono do perfil que estamos visitando
const loggedInUser = ref(null); // Você (a pessoa logada)
const loading = ref(true);
const errorMessage = ref(''); 
const isFollowing = ref(false);
const actionLoading = ref(false);

// Verifica de quem é o perfil
const isMyProfile = computed(() => {
  if (!loggedInUser.value || !profileUser.value) return false;
  return loggedInUser.value.username === profileUser.value.username;
});

// Calcula a foto (com o Rolo Compressor do Docker)
const avatarUrl = computed(() => {
  let url = profileUser.value?.profile_photo_url;
  if (url) {
    const match = url.match(/avatars\/([^/]+)$/);
    if (match) return `http://localhost:8000/storage/avatars/${match[1]}`;
  }
  const name = profileUser.value?.name ? encodeURIComponent(profileUser.value.name) : 'User';
  return `https://ui-avatars.com/api/?name=${name}&background=6F5CFF&color=fff&size=150&bold=true`;
});

const fetchMe = async () => {
  try {
    const { data } = await api.get('/me');
    loggedInUser.value = data.data || data; 
  } catch (error) {
    console.error("Erro ao identificar o usuário logado", error);
  }
};

const fetchProfile = async () => {
  loading.value = true;
  errorMessage.value = '';
  
  try {
    const usernameURL = route.params.username;
    
    const response = await api.get(`/users${usernameURL}`);    
    const responseData = response.data || response;
    
    console.log("DADOS DESEMPACOTADOS:", responseData);

    profileUser.value = responseData.user || responseData;
    isFollowing.value = responseData.is_following || false;
    
  } catch (error) {
    console.error("ERRO REAL DO BACKEND:", error.response?.data || error);
    errorMessage.value = 'User not found.';
  } finally {
    loading.value = false;
  }
};

watch(() => route.params.username, () => {
  fetchProfile();
});

onMounted(async () => {
  if (!getToken()) {
    router.push('/login');
    return;
  }
  await fetchMe();
  await fetchProfile();
});

const handleFollowToggle = async () => {
  if (actionLoading.value) return;
  actionLoading.value = true;
  
  try {
    const { data } = await api.post(`/users/${profileUser.value.username}/follow`);
    isFollowing.value = data.is_following;
    
    // Atualiza o contador na hora
    if (data.is_following) {
      profileUser.value.followers_count++;
    } else {
      profileUser.value.followers_count--;
    }
  } catch (error) {
    console.error("Erro ao seguir", error);
  } finally {
    actionLoading.value = false;
  }
};

// O Logout que limpa a memória
const handleLogout = () => {
  localStorage.removeItem('auth_token');
  window.location.href = '/login'; 
};
</script>

<template>
  <div class="profile-container">
    
    <!-- Tela de Carregamento -->
    <div v-if="loading" class="center-message">
      <i class="fa-solid fa-spinner fa-spin fa-2xl" style="color: #6F5CFF;"></i>
    </div>
    
    <!-- Tela de Erro (Usuário não existe) -->
    <div v-else-if="errorMessage" class="center-message error-box">
      <i class="fa-solid fa-triangle-exclamation fa-2xl" style="color: #ff5d5d;"></i>
      <p>{{ errorMessage }}</p>
      <button @click="router.push('/')" class="btn-back">Go to Feed</button>
    </div>
    
    <div v-else-if="profileUser" class="gravityly-layout">
      
      <header class="profile-header">
        <div class="profile-avatar-container">
          <img class="profile-avatar" :src="avatarUrl" alt="User avatar">
        </div>

        <section class="profile-info">
          <div class="info-top">
            <h2 class="username">{{ profileUser.username }}</h2>
            
            <template v-if="isMyProfile">
              <router-link to="/profile/edit" class="btn-edit">Edit profile</router-link>
              <a href="#" @click.prevent="handleLogout" class="settings-icon" title="Log Out">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
              </a>
            </template>
            
            <template v-else>
              <button 
                @click="handleFollowToggle" 
                class="btn-edit" 
                :class="{ 'btn-following': isFollowing }"
                :disabled="actionLoading"
              >
                {{ actionLoading ? '...' : (isFollowing ? 'Following' : 'Follow') }}
              </button>
            </template>

          </div>

          <ul class="info-stats">
            <li><span class="stat-count">{{ profileUser.posts_count || 0 }}</span> posts</li>
            <li><span class="stat-count">{{ profileUser.followers_count || 0 }}</span> followers</li>
            <li><span class="stat-count">{{ profileUser.following_count || 0 }}</span> following</li>
          </ul>

          <div class="info-bio">
            <h1 class="fullname">{{ profileUser.name || profileUser.username }}</h1>
            <div class="bio-text">
              {{ profileUser.bio || (isMyProfile ? 'Add a bio in Edit Profile.' : '') }}
            </div>
          </div>
        </section>
      </header>

      <div class="profile-tabs">
        <a href="#" class="tab active-tab"><i class="fa-solid fa-table-cells"></i> POSTS</a>
        <a href="#" v-if="isMyProfile" class="tab"><i class="fa-regular fa-bookmark"></i> SAVED</a>
      </div>

      <div class="posts-grid">
        <div class="empty-posts">
          <i class="fa-solid fa-camera fa-2xl"></i>
          <h2>No posts yet</h2>
        </div>
      </div>
      
    </div>

    <div v-else class="center-message error-box">
      <i class="fa-solid fa-bug fa-2xl" style="color: #FFC857;"></i>
      <p>Ocorreu um erro desconhecido ao carregar a interface.</p>
      <button @click="router.push('/')" class="btn-back">Voltar ao Início</button>
    </div>

  </div>
</template>

<style scoped>
/* Main Container */
.profile-container {
  max-width: 935px;
  margin: 0 auto;
  padding: 30px 20px 80px 20px;
  color: #fff;
}

.center-message {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 60vh;
  text-align: center;
}

.error-box {
  background: rgba(255, 93, 93, 0.1);
  border: 1px solid rgba(255, 93, 93, 0.3);
  border-radius: 12px;
  padding: 30px;
  max-width: 400px;
  margin: 0 auto;
}

.btn-back {
  background-color: transparent;
  color: #fff;
  border: 1px solid #6F5CFF;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
  margin-top: 15px;
  transition: 0.3s;
}

.btn-back:hover {
  background-color: rgba(111, 92, 255, 0.2);
}

/* Header: Avatar + Info */
.profile-header {
  display: flex;
  margin-bottom: 44px;
}

.profile-avatar-container {
  flex: 1;
  display: flex;
  justify-content: center;
  margin-right: 30px;
}

.profile-avatar {
  width: 150px;
  height: 150px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid rgba(111, 92, 255, 0.5); 
  box-shadow: 0 4px 15px rgba(111, 92, 255, 0.2);
}

.profile-info {
  flex: 2;
  display: flex;
  flex-direction: column;
}

/* Line 1 */
.info-top {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
  gap: 15px;
}

.username {
  font-size: 1.25rem;
  font-weight: 500;
  margin: 0;
  color: #C9C2E8; 
}

.btn-edit {
  background-color: rgba(255, 255, 255, 0.1);
  color: #fff;
  border-radius: 8px;
  padding: 6px 16px;
  font-size: 14px;
  font-weight: bold;
  text-decoration: none;
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-edit:hover { 
  background-color: rgba(255, 255, 255, 0.2); 
}

.btn-following {
  background-color: transparent;
  border: 1px solid #6F5CFF;
  color: #fff;
}

.btn-following:hover {
  background-color: rgba(111, 92, 255, 0.1);
  border-color: #ff5d5d;
  color: #ff5d5d;
}

.settings-icon {
  color: #ff5d5d; /* Deixei o ícone de logout vermelho para destacar */
  font-size: 1.2rem;
  text-decoration: none;
  transition: 0.2s;
}
.settings-icon:hover { opacity: 0.7; }

/* Line 2 */
.info-stats {
  display: flex;
  list-style: none;
  padding: 0;
  margin: 0 0 20px 0;
  gap: 40px;
  font-size: 1rem;
}

.stat-count {
  font-weight: bold;
  color: #FFC857; 
}

/* Line 3 */
.info-bio {
  font-size: 0.95rem;
  line-height: 1.5;
}

.fullname {
  font-weight: bold;
  font-size: 1.05rem;
  margin: 0 0 5px 0;
}

.bio-text {
  white-space: pre-wrap; 
  color: #C9C2E8;
}

/* Tabs */
.profile-tabs {
  display: flex;
  justify-content: center;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  gap: 60px;
}

.tab {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 15px 0;
  color: #a8a8a8;
  font-size: 12px;
  font-weight: bold;
  text-decoration: none;
  letter-spacing: 1px;
}

.active-tab {
  color: #fff;
  border-top: 1px solid #FFC857; 
  margin-top: -1px;
}

/* Grid */
.posts-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 4px;
}

.empty-posts {
  grid-column: 1 / -1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 300px;
  color: #C9C2E8;
}

.empty-posts h2 {
  font-size: 1.3rem;
  font-weight: 400;
  margin-top: 15px;
}

/* Responsiveness */
@media (max-width: 735px) {
  .profile-header {
    flex-direction: column;
    padding-left: 0;
  }
  .profile-avatar-container {
    margin-right: 0;
    margin-bottom: 20px;
  }
  .info-stats {
    justify-content: center;
    gap: 20px;
  }
  .info-top {
    justify-content: center;
  }
  .info-bio {
    text-align: center;
  }
  .profile-tabs {
    gap: 20px;
  }
}
</style>