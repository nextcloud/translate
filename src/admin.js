import Vue from 'vue'
import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip.js'
import App from './components/ViewAdmin.vue'
import AppGlobal from './mixins/AppGlobal.js'

Vue.mixin(AppGlobal)
Vue.directive('tooltip', Tooltip)

global.Recognize = new Vue({
	el: '#translate',
	render: h => h(App),
})
