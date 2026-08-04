import { createRouter, createWebHistory } from 'vue-router';
import HomeView from '../views/HomeView.vue';
import LoginView from '../views/LoginView.vue';
import RegisterView from '../views/RegisterView.vue';
import ProfileView from '../views/ProfileView.vue';
import UserProfileView from '../views/UserProfileView.vue';
import EditProfileView from '../views/EditProfileView.vue';
import NotFoundView from '../views/NotFoundView.vue';
import SearchView from '../views/SearchView.vue';
import MessagesView from '../views/MessagesView.vue';
import CreatePostView from '../views/CreatePostView.vue';
import PostDetailView from '../views/PostDetailView.vue';
import { isAuthenticated } from '../services/api';

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView,
    meta: { requiresAuth: true },
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { guestOnly: true },
  },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    meta: { requiresAuth: true },
  },
  {
    path: '/profile/edit',
    name: 'edit-profile',
    component: EditProfileView,
    meta: { requiresAuth: true },
  },
  {
    path: '/profile/:username',
    name: 'user-profile',
    component: UserProfileView,
    meta: { requiresAuth: true },
  },
  {
    path: '/search',
    name: 'search',
    component: SearchView,
    meta: { requiresAuth: true },
  },
  {
    path: '/messages',
    name: 'messages',
    component: MessagesView,
    meta: { requiresAuth: true },
  },
  {
    path: '/posts/create',
    name: 'create-post',
    component: CreatePostView,
    meta: { requiresAuth: true },
  },
  {
    path: '/post/create',
    redirect: { name: 'create-post' },
  },
  {
    path: '/posts/:postId(\\d+)',
    alias: '/post/:postId(\\d+)',
    name: 'post-detail',
    component: PostDetailView,
    meta: { requiresAuth: true },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: NotFoundView,
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((destinationRoute) => {
  if (destinationRoute.meta.guestOnly && isAuthenticated()) {
    return { name: 'home' };
  }

  if (destinationRoute.meta.requiresAuth && !isAuthenticated()) {
    return {
      name: 'login',
      query: { redirect: destinationRoute.fullPath },
    };
  }

  return true;
});

export default router;
