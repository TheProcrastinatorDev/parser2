# Frontend Standards (Vue 3 + TypeScript + Inertia.js)

## Architecture Overview

This project uses:
- **Vue 3** with Composition API
- **TypeScript** for type safety
- **Inertia.js** for server-side routing
- **Tailwind CSS 4** for styling
- **Vite** for build tooling

## Component Structure

```
resources/js/
├── pages/                  # Inertia.js pages (route components)
│   ├── Dashboard.vue
│   ├── campaigns/
│   │   ├── Index.vue
│   │   ├── Create.vue
│   │   └── Edit.vue
│   └── auth/
│       ├── Login.vue
│       └── Register.vue
├── components/            # Reusable components
│   ├── ui/               # UI component library (reka-ui, etc.)
│   ├── forms/            # Form components
│   └── layout/           # Layout components
├── layouts/              # Layout wrappers
│   ├── AppLayout.vue
│   └── GuestLayout.vue
├── composables/          # Vue composables (shared logic)
│   ├── useParsing.ts
│   └── useCampaigns.ts
├── types/                # TypeScript type definitions
│   ├── models.ts
│   └── api.ts
└── app.ts               # Application entry point
```

## Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Components | PascalCase | `CampaignForm.vue`, `ParsingResult.vue` |
| Pages | PascalCase | `Dashboard.vue`, `Index.vue` |
| Composables | use prefix, camelCase | `useParsing.ts`, `useCampaigns.ts` |
| Props | camelCase | `campaignId`, `isActive` |
| Events | kebab-case | `@campaign-updated`, `@parsing-complete` |
| CSS classes | kebab-case | `.campaign-card`, `.parsing-status` |
| Files | PascalCase | `CampaignForm.vue` |

## Component Pattern (Composition API)

### Basic Component Structure

```vue
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { ParsingCampaign } from '@/types/models'

// Props
interface Props {
  campaign: ParsingCampaign
  isEditable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isEditable: true,
})

// Emits
interface Emits {
  (e: 'update:campaign', campaign: ParsingCampaign): void
  (e: 'delete', id: number): void
}

const emit = defineEmits<Emits>()

// Composables
const { auth } = usePage().props

// Reactive state
const isLoading = ref(false)
const formData = ref({ ...props.campaign })

// Computed
const canEdit = computed(() => {
  return props.isEditable && auth.user.id === props.campaign.user_id
})

// Methods
const handleUpdate = async () => {
  isLoading.value = true
  try {
    // API call
    emit('update:campaign', formData.value)
  } catch (error) {
    console.error('Update failed:', error)
  } finally {
    isLoading.value = false
  }
}

// Lifecycle
onMounted(() => {
  // Initialization logic
})
</script>

<template>
  <div class="campaign-form">
    <h2>{{ campaign.name }}</h2>

    <button
      v-if="canEdit"
      @click="handleUpdate"
      :disabled="isLoading"
      class="btn-primary"
    >
      {{ isLoading ? 'Saving...' : 'Save' }}
    </button>
  </div>
</template>

<style scoped>
.campaign-form {
  @apply p-4 bg-white rounded-lg shadow;
}
</style>
```

## TypeScript Types

### Model Types

```typescript
// resources/js/types/models.ts

export interface User {
  id: number
  name: string
  email: string
  email_verified_at: string | null
  created_at: string
  updated_at: string
}

export interface ParsingCampaign {
  id: number
  user_id: number
  name: string
  parser_type: string
  configuration: Record<string, any>
  schedule: string | null
  is_active: boolean
  created_at: string
  updated_at: string
  user?: User
  sources_count?: number
  results_count?: number
}

export interface ParsingSource {
  id: number
  campaign_id: number
  name: string
  parser_type: string
  source_url: string
  configuration: Record<string, any>
  is_active: boolean
  last_parsed_at: string | null
  created_at: string
  updated_at: string
}

export interface ParsingResult {
  id: number
  source_id: number
  campaign_id: number
  content: string
  parsed_data: Record<string, any>
  normalized_data: NormalizedData | null
  categories: Category[]
  status: 'pending' | 'processing' | 'completed' | 'failed'
  created_at: string
  updated_at: string
}

export interface NormalizedData {
  title: string
  body: string
  author: string | null
  published_at: string | null
  metadata: Record<string, any>
}

export interface Category {
  id: number
  name: string
  type: string
  confidence?: number
}
```

### API Response Types

```typescript
// resources/js/types/api.ts

export interface ApiResponse<T = any> {
  success: boolean
  data: T
  message?: string
}

export interface PaginatedResponse<T> {
  success: boolean
  data: T[]
  meta: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
  links: {
    first: string
    last: string
    prev: string | null
    next: string | null
  }
}

export interface ApiError {
  success: false
  message: string
  errors?: Record<string, string[]>
  code?: string
}
```

## Composables Pattern

### Example Composable

```typescript
// resources/js/composables/useParsing.ts

import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import type { ParsingCampaign, ParsingResult } from '@/types/models'
import type { ApiResponse } from '@/types/api'

export function useParsing() {
  const isExecuting = ref(false)
  const lastResult = ref<ParsingResult | null>(null)
  const error = ref<string | null>(null)

  const executeCampaign = async (campaignId: number) => {
    isExecuting.value = true
    error.value = null

    try {
      const response = await fetch(`/api/campaigns/${campaignId}/execute`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        },
      })

      const data: ApiResponse<ParsingResult> = await response.json()

      if (data.success) {
        lastResult.value = data.data
      } else {
        error.value = data.message ?? 'Execution failed'
      }
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
    } finally {
      isExecuting.value = false
    }
  }

  const hasResult = computed(() => lastResult.value !== null)
  const hasError = computed(() => error.value !== null)

  return {
    isExecuting,
    lastResult,
    error,
    hasResult,
    hasError,
    executeCampaign,
  }
}
```

### Usage in Component

```vue
<script setup lang="ts">
import { useParsing } from '@/composables/useParsing'

const { isExecuting, lastResult, error, executeCampaign } = useParsing()

const handleExecute = async (campaignId: number) => {
  await executeCampaign(campaignId)
  if (lastResult.value) {
    console.log('Execution successful:', lastResult.value)
  }
}
</script>
```

## Inertia.js Patterns

### Navigation

```typescript
import { router } from '@inertiajs/vue3'

// Navigate to page
router.visit('/campaigns')

// POST request
router.post('/campaigns', {
  name: 'New Campaign',
  parser_type: 'feeds',
})

// With callbacks
router.post('/campaigns', data, {
  onSuccess: () => {
    console.log('Created!')
  },
  onError: (errors) => {
    console.error('Validation errors:', errors)
  },
})
```

### Form Handling

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  parser_type: 'feeds',
  configuration: {},
  is_active: true,
})

const submit = () => {
  form.post('/campaigns', {
    onSuccess: () => {
      form.reset()
    },
  })
}
</script>

<template>
  <form @submit.prevent="submit">
    <input v-model="form.name" type="text" />
    <span v-if="form.errors.name" class="error">{{ form.errors.name }}</span>

    <button type="submit" :disabled="form.processing">
      {{ form.processing ? 'Saving...' : 'Save' }}
    </button>
  </form>
</template>
```

## Styling with Tailwind CSS

### Component Styling

```vue
<template>
  <div class="campaign-card">
    <h3 class="text-lg font-semibold text-gray-900">
      {{ campaign.name }}
    </h3>

    <p class="mt-2 text-sm text-gray-600">
      {{ campaign.parser_type }}
    </p>

    <div class="mt-4 flex items-center justify-between">
      <span
        :class="[
          'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
          campaign.is_active
            ? 'bg-green-100 text-green-800'
            : 'bg-gray-100 text-gray-800'
        ]"
      >
        {{ campaign.is_active ? 'Active' : 'Inactive' }}
      </span>

      <button
        @click="handleEdit"
        class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500"
      >
        Edit
      </button>
    </div>
  </div>
</template>

<style scoped>
.campaign-card {
  @apply rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md;
}
</style>
```

## Best Practices

### DO:

- ✅ Use Composition API (not Options API)
- ✅ Define TypeScript types for all props
- ✅ Use composables for shared logic
- ✅ Use Inertia.js for navigation
- ✅ Use Tailwind CSS utility classes
- ✅ Define component emits with types
- ✅ Use `<script setup>` syntax
- ✅ Extract complex computed logic into functions
- ✅ Use `withDefaults()` for prop defaults
- ✅ Keep components focused and small

### DON'T:

- ❌ Use Options API (use Composition API)
- ❌ Skip TypeScript types
- ❌ Use inline styles (use Tailwind)
- ❌ Mutate props directly
- ❌ Use `any` type excessively
- ❌ Create deeply nested components
- ❌ Skip error handling
- ❌ Use `@ts-ignore` without good reason

## Error Handling

```vue
<script setup lang="ts">
import { ref } from 'vue'
import type { ApiError } from '@/types/api'

const error = ref<string | null>(null)
const isLoading = ref(false)

const handleAction = async () => {
  isLoading.value = true
  error.value = null

  try {
    const response = await fetch('/api/endpoint')
    const data = await response.json()

    if (!response.ok) {
      const apiError = data as ApiError
      error.value = apiError.message
      return
    }

    // Success handling
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Unknown error occurred'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div>
    <div v-if="error" class="alert alert-error">
      {{ error }}
    </div>

    <button @click="handleAction" :disabled="isLoading">
      {{ isLoading ? 'Loading...' : 'Submit' }}
    </button>
  </div>
</template>
```

## Testing

(See [testing-standards.md](testing-standards.md) for complete guide)

### Component Testing Example

```typescript
import { mount } from '@vue/test-utils'
import CampaignCard from '@/components/CampaignCard.vue'

describe('CampaignCard', () => {
  it('renders campaign name', () => {
    const campaign = {
      id: 1,
      name: 'Test Campaign',
      parser_type: 'feeds',
      is_active: true,
    }

    const wrapper = mount(CampaignCard, {
      props: { campaign },
    })

    expect(wrapper.text()).toContain('Test Campaign')
  })

  it('emits delete event', async () => {
    const wrapper = mount(CampaignCard, {
      props: { campaign: { id: 1, name: 'Test' } },
    })

    await wrapper.find('.btn-delete').trigger('click')

    expect(wrapper.emitted('delete')).toBeTruthy()
  })
})
```

## Summary

**Key Principles:**
1. Use Composition API with TypeScript
2. Define types for all props and emits
3. Use composables for shared logic
4. Follow Inertia.js patterns for navigation
5. Use Tailwind CSS for styling
6. Keep components focused and testable
7. Handle errors gracefully
8. Write type-safe code
