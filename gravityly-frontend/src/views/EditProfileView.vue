<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { userStore } from '../store';
import { api, getFallbackAvatarUrl, getProfileAvatarUrl } from '../services/api';

const router = useRouter();
const error = ref('');
const successMessage = ref('');
const loading = ref(false);

const fileInput = ref(null);
const selectedFile = ref(null);
const previewUrl = ref(null);
let redirectTimer = null;

const form = ref({
  name: '',
  username: '',
  bio: '',
  profile_photo_url: ''
});

const displayAvatar = computed(() => {
  if (previewUrl.value) return previewUrl.value;

  return getProfileAvatarUrl(form.value);
});

const revokePreviewUrl = () => {
  if (previewUrl.value?.startsWith('blob:')) {
    URL.revokeObjectURL(previewUrl.value);
  }

  previewUrl.value = null;
};

const loadProfile = async () => {
  try {
    const responsePayload = await api.get('/me');
    const userProfile = responsePayload?.data;

    if (!userProfile || typeof userProfile !== 'object') {
      throw new Error('The server returned an invalid profile response.');
    }

    form.value.name = userProfile.name;
    form.value.username = userProfile.username;
    form.value.bio = userProfile.bio || '';
    form.value.profile_photo_url = userProfile.profile_photo_url || '';
  } catch (errorResponse) {
    if (errorResponse.status === 401) {
      return;
    }

    console.error('Failed to load profile:', errorResponse);
    error.value = errorResponse.firstMessage ? errorResponse.firstMessage() : errorResponse.message;
  }
};

onMounted(() => {
  void loadProfile();
});

const triggerFileInput = () => {
  fileInput.value?.click();
};

const onFileChange = (event) => {
  const selectedImage = event.target.files?.[0];

  if (selectedImage) {
    revokePreviewUrl();
    selectedFile.value = selectedImage;
    previewUrl.value = URL.createObjectURL(selectedImage);
  }
};

const handleAvatarError = (event) => {
  event.currentTarget.src = getFallbackAvatarUrl(form.value, 200);
};

const handleSave = async () => {
  error.value = '';
  successMessage.value = '';
  loading.value = true;

  const formData = new FormData();
  formData.append('name', form.value.name);
  formData.append('username', form.value.username);
  formData.append('bio', form.value.bio || '');
  
  if (selectedFile.value) {
    formData.append('avatar', selectedFile.value);
  }

  try {
    const responsePayload = await api.put('/me', formData);
    const updatedUser = responsePayload?.data;

    if (!updatedUser || typeof updatedUser !== 'object') {
      throw new Error('The server returned an invalid profile response.');
    }

    userStore.setCurrentUser(updatedUser);
    form.value.profile_photo_url = updatedUser.profile_photo_url || '';
    selectedFile.value = null;
    revokePreviewUrl();

    successMessage.value = 'Profile updated successfully!';
    redirectTimer = window.setTimeout(() => router.push('/profile'), 1500);
  } catch (errorResponse) {
    if (errorResponse.status === 401) {
      return;
    }

    console.error('Failed to update profile:', errorResponse);
    error.value = errorResponse.firstMessage ? errorResponse.firstMessage() : errorResponse.message;
  } finally {
    loading.value = false;
  }
};

onBeforeUnmount(() => {
  revokePreviewUrl();

  if (redirectTimer !== null) {
    window.clearTimeout(redirectTimer);
  }
});
</script>

<template>
  <div class="edit-container">
    <div class="glass-panel">
      
      <div class="edit-header">
        <router-link to="/profile" class="back-btn">
          <i class="fa-solid fa-chevron-left"></i> Cancel
        </router-link>
        <h2>Edit Profile</h2>
        <div style="width: 65px;"></div> 
      </div>

      <div class="avatar-section">
        <div class="avatar-wrapper" @click="triggerFileInput">
          <img class="avatar-preview" :src="displayAvatar" alt="Profile avatar" @error="handleAvatarError" />
          <div class="avatar-overlay">
            <i class="fa-solid fa-camera"></i>
          </div>
        </div>
        <input 
          type="file" 
          ref="fileInput" 
          hidden 
          @change="onFileChange" 
          accept="image/png, image/jpeg, image/jpg" 
        />
        <button type="button" class="change-photo-btn" @click="triggerFileInput">
          Change Profile Photo
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSave" class="form-body">
        
        <transition name="fade">
          <div class="alert-box error" v-if="error">
            <i class="fa-solid fa-circle-exclamation"></i> {{ error }}
          </div>
        </transition>

        <transition name="fade">
          <div class="alert-box success" v-if="successMessage">
            <i class="fa-solid fa-circle-check"></i> {{ successMessage }}
          </div>
        </transition>

        <div class="form-group">
          <label>Name</label>
          <input type="text" v-model="form.name" required placeholder="Your full name" class="glass-input" />
        </div>
        
        <div class="form-group">
          <label>Username</label>
          <input type="text" v-model="form.username" required placeholder="Choose a username" class="glass-input" />
        </div>
        
        <div class="form-group">
          <label>Bio</label>
          <textarea v-model="form.bio" rows="4" placeholder="Write something about yourself..." class="glass-input"></textarea>
        </div>

        <button type="submit" class="btn-submit glow-effect" :disabled="loading">
          <i class="fa-solid fa-spinner fa-spin" v-if="loading"></i> 
          <span v-else>Save Changes</span>
        </button>
      </form>

    </div>
  </div>
</template>

<style scoped>
.edit-container {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  min-height: 100vh;
  padding: 40px 20px 100px 20px;
  color: #fff;
}

/* Glass Panel (Glassmorphism) */
.glass-panel {
  width: 100%;
  max-width: 500px;
  background: rgba(33, 25, 52, 0.7);
  border: 1px solid rgba(111, 92, 255, 0.3);
  border-radius: 24px;
  padding: 30px;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

/* Header */
.edit-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 40px;
}

.edit-header h2 {
  font-size: 1.3rem;
  font-weight: 600;
  margin: 0;
  color: #FFC857;
}

.back-btn {
  color: #C9C2E8;
  font-size: 1rem;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.3s;
}

.back-btn:hover {
  color: #fff;
}

/* Avatar Section */
.avatar-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 35px;
}

.avatar-wrapper {
  position: relative;
  width: 100px;
  height: 100px;
  border-radius: 50%;
  cursor: pointer;
  overflow: hidden;
  border: 3px solid #6F5CFF;
  box-shadow: 0 4px 15px rgba(111, 92, 255, 0.4);
  transition: transform 0.3s ease;
}

.avatar-wrapper:hover {
  transform: scale(1.05);
}

.avatar-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.avatar-wrapper:hover .avatar-overlay {
  opacity: 1;
}

.avatar-overlay i {
  color: white;
  font-size: 1.5rem;
}

.change-photo-btn {
  background: none;
  border: none;
  color: #6F5CFF;
  font-weight: 600;
  font-size: 0.95rem;
  margin-top: 12px;
  cursor: pointer;
  transition: color 0.3s;
}

.change-photo-btn:hover {
  color: #FFC857;
}

/* Form */
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
  font-weight: 500;
  margin-bottom: 8px;
  font-size: 0.9rem;
  color: #C9C2E8;
}

/* Glass-styled Inputs */
.glass-input {
  background-color: rgba(20, 15, 35, 0.5);
  border: 1px solid rgba(111, 92, 255, 0.2);
  color: #fff;
  border-radius: 12px;
  padding: 14px 16px;
  font-size: 1rem;
  outline: none;
  transition: all 0.3s ease;
  resize: vertical;
}

.glass-input:focus {
  border-color: #6F5CFF;
  background-color: rgba(20, 15, 35, 0.8);
  box-shadow: 0 0 0 3px rgba(111, 92, 255, 0.2);
}

.glass-input::placeholder {
  color: rgba(201, 194, 232, 0.4);
}

/* Save Button */
.btn-submit {
  background-color: #6F5CFF;
  color: white;
  border: none;
  border-radius: 12px;
  padding: 14px;
  font-weight: bold;
  font-size: 1.05rem;
  cursor: pointer;
  margin-top: 10px;
  transition: all 0.3s ease;
}

.btn-submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.glow-effect:hover:not(:disabled) {
  background-color: #FFC857;
  color: #211934;
  box-shadow: 0 0 15px rgba(255, 200, 87, 0.4);
}

/* Animated Alerts */
.alert-box {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 10px;
}

.alert-box.error {
  background: rgba(255, 93, 93, 0.1);
  border: 1px solid rgba(255, 93, 93, 0.3);
  color: #ff5d5d;
}

.alert-box.success {
  background: rgba(76, 217, 100, 0.1);
  border: 1px solid rgba(76, 217, 100, 0.3);
  color: #4cd964;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.4s ease, transform 0.4s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
