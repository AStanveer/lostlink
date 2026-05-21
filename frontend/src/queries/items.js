import {useQuery} from "@pinia/colada";
import api from "@/services/api.js";

/**
 * @returns {{ data: import('vue').Ref<Item[] | undefined>, isLoading: import('vue').Ref<boolean>, error: import('vue').Ref<Error | null> }}
 */
export function useItems() {
    return useQuery({
        key: ['items'],
        /** @returns {Promise<Item[]>} */
        query: () => api.get("/items").then(r => r.data)
    });
}