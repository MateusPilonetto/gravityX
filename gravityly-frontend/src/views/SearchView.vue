<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { api, getFallbackAvatarUrl, getProfileAvatarUrl } from '../services/api';

const router = useRouter();

const searchQuery = ref('');
const searchResults = ref([]);
const loading = ref(false);
const searchError = ref('');
const hasSearched = ref(false);
const profileSuggestions = ref([]);
const loadingSuggestions = ref(false);
const suggestionsError = ref('');
const showProfileSuggestions = computed(() => (
    !searchQuery.value.trim() && !hasSearched.value
));
let activeSearchRequestId = 0;
let activeSuggestionsRequestId = 0;

const resetSearchState = () => {
    activeSearchRequestId += 1;
    loading.value = false;
    searchResults.value = [];
    searchError.value = '';
    hasSearched.value = false;
};

const loadProfileSuggestions = async () => {
    const requestId = ++activeSuggestionsRequestId;
    loadingSuggestions.value = true;
    suggestionsError.value = '';

    try {
        const responsePayload = await api.get('/suggestions');
        const users = responsePayload?.data ?? responsePayload;

        if (!Array.isArray(users)) {
            throw new Error('The server returned an invalid suggestions response.');
        }

        if (requestId === activeSuggestionsRequestId) {
            profileSuggestions.value = users;
        }
    } catch (errorResponse) {
        if (requestId !== activeSuggestionsRequestId || errorResponse.status === 401) {
            return;
        }

        console.error('Failed to load profile suggestions:', errorResponse);
        profileSuggestions.value = [];
        suggestionsError.value = 'Could not load profile suggestions. Please try again.';
    } finally {
        if (requestId === activeSuggestionsRequestId) {
            loadingSuggestions.value = false;
        }
    }
};

const searchUsers = async () => {
    const normalizedQuery = searchQuery.value.trim();

    if (!normalizedQuery) {
        resetSearchState();
        return;
    }

    const requestId = ++activeSearchRequestId;
    loading.value = true;
    searchError.value = '';
    hasSearched.value = true;

    try {
        const encodedQuery = encodeURIComponent(normalizedQuery);
        const responsePayload = await api.get(`/search?q=${encodedQuery}`);
        const users = responsePayload?.data ?? responsePayload;

        if (!Array.isArray(users)) {
            throw new Error('The server returned an invalid search response.');
        }

        if (requestId === activeSearchRequestId) {
            searchResults.value = users;
        }
    } catch (errorResponse) {
        if (requestId !== activeSearchRequestId || errorResponse.status === 401) {
            return;
        }

        console.error('Failed to search users:', errorResponse);
        searchResults.value = [];
        searchError.value = 'Could not search users. Please try again.';
    } finally {
        if (requestId === activeSearchRequestId) {
            loading.value = false;
        }
    }
};

const goToProfile = (username) => {
    router.push({ name: 'user-profile', params: { username } });
};

const getAvatarUrl = (user) => getProfileAvatarUrl(user, 100);

const getSuggestionMeta = (user) => {
    const mutualConnectionsCount = Number(user.mutual_connections_count) || 0;

    if (mutualConnectionsCount > 0) {
        return `${mutualConnectionsCount} mutual ${mutualConnectionsCount === 1 ? 'connection' : 'connections'}`;
    }

    const followersCount = Number(user.followers_count) || 0;

    return `${followersCount} ${followersCount === 1 ? 'follower' : 'followers'}`;
};

const handleAvatarError = (event, user) => {
    event.currentTarget.src = getFallbackAvatarUrl(user, 100);
};

onMounted(() => {
    void loadProfileSuggestions();
});
</script>

<template>
    <div class="search-page">
        <div class="search-container">
            <input 
                class="glass-effect search-input" 
                type="search"  
                id="searchInput"
                v-model="searchQuery"
                @input="resetSearchState"
                @keyup.enter="searchUsers"
                placeholder="Search users..."
            >
            <button @click="searchUsers" type="button" class="search-button glass-effect" aria-label="Search users">
                <i class="fa-solid fa-search fa-xl" style="color: #FFC857;"></i>
            </button>
        </div>

        <section v-if="showProfileSuggestions" class="suggestions-section" aria-labelledby="profile-suggestions-title">
            <div class="suggestions-heading">
                <p class="suggestions-kicker">DISCOVER</p>
                <h2 id="profile-suggestions-title">Suggested profiles</h2>
                <p>Find people to follow and keep your feed moving.</p>
            </div>

            <div v-if="loadingSuggestions" class="status-message suggestions-status" role="status">
                <i class="fa-solid fa-spinner fa-spin fa-2xl" style="color: #6F5CFF;"></i>
            </div>

            <div v-else-if="suggestionsError" class="status-message error-state suggestions-status" role="alert">
                <i class="fa-solid fa-triangle-exclamation fa-2xl" style="color: #ff8b8b; margin-bottom: 15px;"></i>
                <p>{{ suggestionsError }}</p>
            </div>

            <div v-else-if="profileSuggestions.length > 0" class="results-grid">
                <button
                    v-for="user in profileSuggestions"
                    :key="user.id"
                    class="user-card glass-effect"
                    @click="goToProfile(user.username)"
                    :aria-label="`Open ${user.username}'s profile`"
                >
                    <img :src="getAvatarUrl(user)" class="card-avatar" alt="User avatar" @error="handleAvatarError($event, user)">
                    <div class="card-info">
                        <h3 class="card-username">@{{ user.username }}</h3>
                        <p class="card-name">{{ user.name || 'Gravityly User' }}</p>
                        <p class="suggestion-meta">{{ getSuggestionMeta(user) }}</p>
                    </div>
                </button>
            </div>

            <p v-else class="suggestions-empty">There are no new profiles to suggest right now.</p>
        </section>

        <div v-else-if="loading" class="status-message">
            <i class="fa-solid fa-spinner fa-spin fa-2xl" style="color: #6F5CFF;"></i>
        </div>
        
        <div v-else-if="searchError" class="status-message error-state" role="alert">
            <i class="fa-solid fa-triangle-exclamation fa-2xl" style="color: #ff8b8b; margin-bottom: 15px;"></i>
            <p>{{ searchError }}</p>
        </div>

        <div v-else-if="hasSearched && searchResults.length === 0" class="status-message empty-state">
            <i class="fa-solid fa-ghost fa-2xl" style="color: rgba(255, 255, 255, 0.2); margin-bottom: 15px;"></i>
            <p>No users found for "{{ searchQuery }}"</p>
        </div>

        <div v-else-if="searchResults.length > 0" class="results-grid">
            <button
                v-for="user in searchResults" 
                :key="user.id" 
                class="user-card glass-effect"
                @click="goToProfile(user.username)"
                :aria-label="`Open ${user.username}'s profile`"
            >
                <img :src="getAvatarUrl(user)" class="card-avatar" alt="User avatar" @error="handleAvatarError($event, user)">
                <div class="card-info">
                    <h3 class="card-username">@{{ user.username }}</h3>
                    <p class="card-name">{{ user.name || 'Gravityly User' }}</p>
                </div>
            </button>
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

.suggestions-section {
    width: 100%;
    max-width: 900px;
}

.suggestions-heading {
    margin-bottom: 1.5rem;
    text-align: center;
}

.suggestions-kicker {
    margin: 0 0 0.35rem;
    color: #FFC857;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.16em;
}

.suggestions-heading h2 {
    margin: 0;
    color: #fff;
    font-size: 1.65rem;
}

.suggestions-heading > p:last-child {
    margin: 0.4rem 0 0;
    color: #a8a8a8;
}

.suggestions-status {
    margin-top: 1.5rem;
}

.suggestions-empty {
    margin: 1.5rem 0 0;
    color: #a8a8a8;
    text-align: center;
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
    color: inherit;
    font: inherit;
    text-align: inherit;
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

.suggestion-meta {
    margin: 0.45rem 0 0;
    color: #C9C2E8;
    font-size: 0.8rem;
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

.error-state p {
    color: #ffb3b3;
    font-size: 1.1rem;
    margin: 0;
}
</style>
