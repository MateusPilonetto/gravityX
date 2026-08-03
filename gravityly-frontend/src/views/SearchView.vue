<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { api, setToken } from '../services/api';

const router = useRouter();
const error = ref('');

const searchQuery = ref('')
const searchResult = ref([])
const loading = ref(false);

const search = async () => {
    if(!searchQuery.value.trim()) return;

    loading.value = true;

    try {
        const {data} = await api.get('/search', {
            params: {
                q: searchQuery.value
            }
        });
        searchResults.value = data;
    } 
    catch (error) {
        console.error("Error on search:", error);
    } 
    finally {
        loading.value = false;
    }
}
</script>

<template>
  <div class="search-container">
    <input 
        class="glass-effect search-input" 
        type="search"  
        id="searchInput"
        v-model="searchQuery"
        @keyup.enter="search"
        placeholder="Search">
    
        <button @click="search" type="submit" class="search-button glass-effect">
            <i class="fa-solid fa-search fa-2xl" style="color: #FFC857; margin-bottom: 20px;"></i>
        </button>
  </div>

  <div v-if="loading">Buscando...</div>
  
  <ul v-if="searchResults.length > 0">
    <li v-for="user in searchResults" :key="user.id">
      {{ user.username }}
    </li>
  </ul>
</template>

<style scoped>
.search-container {
    width: 100vw;
    height: auto;

    display: flex;
    justify-content: center;
}

.search-input {
    width: 50vw;
    padding: 1rem 1.5rem;
    border-radius: 1rem;
    border: 1px solid rgba(111, 92, 255, 0.3);
    background: rgba(3, 2, 4, 0.6);
    color: white;
    font-size: 1rem;
    box-sizing: border-box;
    outline: none;
    transition: border-color 0.3s ease;
}

.search-button {
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 1.5rem;
    width: 4rem;
    height: 4rem;
    padding-top: 1em;
    margin-left: 1em;
  
    transition: all 0.3s ease; 
    cursor: pointer;
}

.search-button:hover {
      background: rgba(46, 46, 46, 0.25);

}
</style>