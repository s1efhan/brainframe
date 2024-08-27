import { ref } from 'vue'

export const sessionId = ref(0)
export const updateSessionId = (newId) => {
  sessionId.value = newId
}