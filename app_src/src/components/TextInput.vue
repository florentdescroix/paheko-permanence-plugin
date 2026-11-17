<template>
  <div
    class="input-sizer"
    :class="$attrs.class"
    :data-value="model"
    :style="$attrs.style"
  >
    <textarea
      v-if="multiple"
      v-model="model"
      :placeholder="placeholder"
      rows="1"
      v-bind="{...$attrs, style:'', class:''}"
    />

    <input
      v-else
      v-model="model"
      :placeholder="placeholder"
      v-bind="{...$attrs, style:'', class:''}"
    >
  </div>
</template>

<script lang="ts" setup>
  defineOptions({
    inheritAttrs: false,
  })

  const { multiple = false, placeholder = '' } = defineProps<{
    multiple?: boolean
    placeholder?: string
  }>()

  const model = defineModel<string | number>()
</script>

<style lang="scss">

.input-sizer {
  font-size: 1em;
  display: inline-grid;
  width: max-content;
  align-items: stretch;
  max-width: 100%;

  ::placeholder {
    color: rgb(var(--v-theme-grey));
  }

  input,
  textarea {
    &:not(:disabled) {
        background: lightgrey;
    }
    &:focus-within {
        z-index: 1;
    }
  }

  &::before,
  input,
  textarea {
    width: auto;
    grid-area: 1 / 1;
    font: inherit;
    color: inherit;
    font-size: inherit;
    font-weight: inherit;
    text-transform: inherit;
    font-variant: inherit;
    text-align: inherit;
    letter-spacing: inherit;
    margin: 0;
    padding: 0;
    resize: none;
    overflow: hidden;
    background: none;
    appearance: none;
    border: none;
    border-radius: 3px;
    cursor: text;
  }

  &::before {
    content: attr(data-value) ' ';
    visibility: hidden;
    white-space: pre-wrap;
    pointer-events: none;;
  }
}
</style>
