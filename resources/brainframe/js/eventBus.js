import { ref } from 'vue'

export const sessionId = ref('')
export const updateSessionId = (newId) => {
  sessionId.value = newId
}