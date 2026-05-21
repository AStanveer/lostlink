import {defineStore} from "pinia";
import {ref} from "vue";
import api from "../services/api.js";

export const useItemStore = defineStore("items", () => {
    /** @type {import('vue').Ref<Array<Item>>} */
    const items = ref([]);

    const loading = ref(true);
    const error = ref(null);

    async function fetchItems() {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get("/items");
            console.log(response);
            items.value = response.data;
        } catch (e) {
            error.value = e.message;
        } finally {
            loading.value = false;
        }
    }

    return {items, loading, error, fetchItems};
});