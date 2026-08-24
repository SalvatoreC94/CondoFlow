<script setup>
import { computed } from "vue";

const props = defineProps({
  name: { type: String, default: "" },
  size: { type: String, default: "md" },
});

const initials = computed(() =>
  props.name
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((p) => p[0]?.toUpperCase())
    .join(""),
);

const sizes = {
  sm: "h-8 w-8 text-xs",
  md: "h-10 w-10 text-sm",
  lg: "h-14 w-14 text-lg",
};
</script>

<template>
  <div
    class="flex flex-shrink-0 items-center justify-center rounded-full bg-primary-100 font-semibold text-primary-700"
    :class="sizes[size]"
  >
    {{ initials || "?" }}
  </div>
</template>
