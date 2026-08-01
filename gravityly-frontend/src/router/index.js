import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'

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
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const hasToken = localStorage.getItem('auth_token');
  const isAuthPage = ['/login', '/register'].includes(to.path);

  if (!hasToken && !isAuthPage) {
    next('/login');
  } else if (hasToken && isAuthPage) {
    next('/');
  } else {
    next();
  }
});

export default router