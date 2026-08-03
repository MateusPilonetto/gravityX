<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../services/api';

const router = useRouter();

const searchQuery = ref('');
const searchResults = ref([]); 
const loading = ref(false);

const search = async () => {
    if(!searchQuery.value.trim()) return;

    loading.value = true;

    try {
        const termo = encodeURIComponent(searchQuery.value);
        const response = await api.get(`/search?q=${termo}`);
        
        searchResults.value = response.data || response || []; 
    } 
    catch (error) {
        console.error("Error on search:", error);
    } 
    finally {
        loading.value = false;
    }
}

const goToProfile = (username) => {
    router.push(`/profile/${username}`);
};

const getAvatarUrl = (user) => {
    let url = user.profile_photo_url;
    if (url) {
        const match = url.match(/avatars\/([^/]+)$/);
        if (match) return `http://localhost:8000/storage/avatars/${match[1]}`;
    }
    const name = user.name ? encodeURIComponent(user.name) : 'User';
    return `https://ui-avatars.com/api/?name=${name}&background=6F5CFF&color=fff&size=100&bold=true`;
};
</script>

<template>
    <div class="search-page">
        <div class="search-container">
            <input 
                class="glass-effect search-input" 
                type="search"  
                id="searchInput"
                v-model="searchQuery"
                @keyup.enter="search"
                placeholder="Search users..."
            >
            <button @click="search" type="submit" class="search-button glass-effect">
                <i class="fa-solid fa-search fa-xl" style="color: #FFC857;"></i>
            </button>
        </div>

        <div v-if="loading" class="status-message">
            <i class="fa-solid fa-spinner fa-spin fa-2xl" style="color: #6F5CFF;"></i>
        </div>
        
        <div v-else-if="searchQuery && searchResults.length === 0" class="status-message empty-state">
            <i class="fa-solid fa-ghost fa-2xl" style="color: rgba(255, 255, 255, 0.2); margin-bottom: 15px;"></i>
            <p>No users found for "{{ searchQuery }}"</p>
        </div>

        <div v-else-if="searchResults.length > 0" class="results-grid">
            <div 
                v-for="user in searchResults" 
                :key="user.id" 
                class="user-card glass-effect"
                @click="goToProfile(user.username)"
            >
                <img :src="getAvatarUrl(user)" class="card-avatar" alt="Avatar">
                <div class="card-info">
                    <h3 class="card-username">@{{ user.username }}</h3>
                    <p class="card-name">{{ user.name || 'Gravityly User' }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.search-page {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    min-height: 80vh;
    padding: 2rem;
    box-sizing: border-box;
}

.search-container {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    max-width: 600px;
    margin-bottom: 3rem;
}

.search-input {
    flex: 1;
    padding: 1.2rem 1.5rem;
    border-radius: 1.5rem;
    border: 1px solid rgba(111, 92, 255, 0.3);
    background: rgba(3, 2, 4, 0.6);
    color: white;
    font-size: 1.1rem;
    outline: none;
    transition: all 0.3s ease;
}

.search-input:focus {
    border-color: #6F5CFF;
    box-shadow: 0 0 15px rgba(111, 92, 255, 0.2);
}

.search-button {
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 1.5rem;
    width: 4rem;
    height: 4rem;
    margin-left: 1rem;
    border: 1px solid rgba(111, 92, 255, 0.3);
    background: rgba(3, 2, 4, 0.6);
    transition: all 0.3s ease; 
    cursor: pointer;
}

.search-button:hover {
    background: rgba(111, 92, 255, 0.2);
    transform: translateY(-2px);
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1.5rem;
    width: 100%;
    max-width: 900px;
}

.user-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2rem 1rem;
    border-radius: 1rem;
    border: 1px solid rgba(111, 92, 255, 0.2);
    background: rgba(3, 2, 4, 0.6);
    backdrop-filter: blur(10px);
    cursor: pointer;
    transition: all 0.3s ease;
}

.user-card:hover {
    transform: translateY(-5px);
    border-color: #6F5CFF;
    box-shadow: 0 10px 25px rgba(111, 92, 255, 0.15);
}

.card-avatar {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #FFC857;
    margin-bottom: 1rem;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
}

.card-info {
    text-align: center;
}

.card-username {
    margin: 0;
    color: #fff;
    font-size: 1.1rem;
    font-weight: 600;
}

.card-name {
    margin: 5px 0 0 0;
    color: #a8a8a8;
    font-size: 0.9rem;
}

.status-message {
    margin-top: 4rem;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.empty-state p {
    color: #a8a8a8;
    font-size: 1.1rem;
    margin: 0;
}
</style>