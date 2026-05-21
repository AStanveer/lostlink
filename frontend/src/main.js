import {createApp} from 'vue'
import {createPinia} from 'pinia'
import App from './App.vue'
import router from './router'
import './assets/main.css'
import "./index.css"
import {PiniaColada} from "@pinia/colada";

const app = createApp(App)

app.use(createPinia())
app.use(PiniaColada, {
    queryOptions: {
        staleTime: 60_000,
    }
})
app.use(router)
app.mount('#app')
