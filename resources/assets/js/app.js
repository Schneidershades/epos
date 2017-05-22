require('./bootstrap');

// Components
Vue.component('payments-fastcash', require('./components/Payments/FastCash.vue'));
Vue.component('payments-cash', require('./components/Payments/Cash.vue'));
Vue.component('payments-card', require('./components/Payments/Card.vue'));
Vue.component('checkout', require('./components/Checkout.vue'));
Vue.component('dashboard-md', require('./components/DashboardMD.vue'));
Vue.component('basket-line', require('./components/Basket/Line.vue'));
Vue.component('models-payment', require('./components/Models/Payment.vue'));
Vue.component('models-product', require('./components/Models/Product.vue'));
Vue.component('clock', require('./components/Clock.vue'));
Vue.component('items', require('./components/Items.vue'));
Vue.component('categories', require('./components/Categories.vue'));
Vue.component('basket', require('./components/Basket.vue'));
Vue.component('item', require('./components/Item.vue'));
Vue.component('dashboard', require('./components/Dashboard.vue'));

const app = new Vue({
    el: '#app'
});
