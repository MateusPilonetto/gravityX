import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import ProfileView from '../views/ProfileView.vue'
import EditProfileView from '../views/EditProfileView.vue'
import { isAuthenticated } from '../services/api'

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView
  },
  {
    path: '/profile:username(.*)',
    name: 'profile',
    component: ProfileView
  },
  {
    path: '/profile/edit',
    name: 'edit-profile',
    component: EditProfileView
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const isAuthPage = ['/login', '/register'].includes(to.path);

  if (!isAuthenticated() && !isAuthPage) {
    next('/login');
  } else if (isAuthenticated() && isAuthPage) {
    next('/');
  } else {
    next();
  }
});

export default router