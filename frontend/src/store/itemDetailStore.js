import {defineStore} from "pinia";
import {useRoute} from "vue-router";
import {useAuthStore} from "@/store/authStore";
import {computed, ref} from "vue";


export const useItemDetailStore = defineStore("item", () => {
    const route = useRoute();
    const auth = useAuthStore();

    /** @type {Ref<Item | null>} */
    const item = ref(null);

    /** @type {Ref<boolean>} */
    const loading = ref(true);

    /** @type {Ref<boolean>} */
    const showClaimModal = ref(false);

    /** @type {Ref<boolean>} */
    const submitting = ref(false);

    const proofBase64 = ref(null);
    const proofPreview = ref(null);
    const fileInput = ref(null);

    /** @type {Ref<string>} */
    const claimError = ref('');

    /** @type {Ref<string>} */
    const claimSuccess = ref('');

    const claimErrors = ref({});
    const toast = ref({message: '', type: ''});
    const claimForm = ref({description: ''});

    const lostItemId = computed(() => route.query.lost_item_id || null)
    const isOwnItem = computed(() => auth.user?.id === item.value?.posted_by)
    const canClaim  = computed(() =>
        auth.isLoggedIn &&
        !isOwnItem.value &&
        item.value?.report_type === 'found' &&
        item.value?.status === 'active'
    )

    return {
        item,
        loading,
        showClaimModal,
        submitting,
        proofBase64, proofPreview, fileInput,
        claimError, claimErrors, claimSuccess,
        toast,
        claimForm,
        lostItemId,
        isOwnItem,
        canClaim
    }
})