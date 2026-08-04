import { reactive } from 'vue';

export const userStore = reactive({
  currentUser: null,
  
  setCurrentUser(user) {
    this.currentUser = user;
  },
  
  clearUser() {
    this.currentUser = null;
  }
});