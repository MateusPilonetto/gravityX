import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import ProfileView from '../views/ProfileView.vue'
import EditProfileView from '../views/EditProfileView.vue'
import { isAuthenticated } from '../services/api'
import SearchView from '@/views/SearchView.vue'
import MessagesView from '@/views/MessagesView.vue'
import CreatePostView from '@/views/CreatePostView.vue'

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
  },
  {
    path: '/search',
    name: 'search',
    component: SearchView
  },
  {
    path: '/messages',
    name: 'Message',
    component: MessagesView
  },
  {
    path: '/post/create',
    name: 'create-post',
    component: CreatePostView
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