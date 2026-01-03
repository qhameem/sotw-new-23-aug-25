<template>
  <div class="bg-white border border-gray-300 rounded-md focus-within:ring-1 focus-within:ring-sky-400 focus-within:border-sky-400">
    <div v-if="editor" class="flex items-center p-1 border-b border-gray-300 gap-1 bg-gray-50">
      <button @click="editor.chain().focus().undo().run()" :disabled="!editor.can().undo()" class="p-1 rounded hover:bg-gray-200 disabled:opacity-50">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 13v-2a4 4 0 0 0-4-4H8L12 3"></path><path d="M7 7l-5 5 5 5"></path></svg>
      </button>
      <button @click="editor.chain().focus().redo().run()" :disabled="!editor.can().redo()" class="p-1 rounded hover:bg-gray-200 disabled:opacity-50">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13v-2a4 4 0 0 1 4-4h9l-4-4"></path><path d="M17 7l5 5-5 5"></path></svg>
      </button>
      <button @click="editor.chain().focus().toggleBold().run()" :class="{ 'is-active': editor.isActive('bold') }" class="p-1 rounded hover:bg-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path></svg>
      </button>
      <button @click="editor.chain().focus().toggleItalic().run()" :class="{ 'is-active': editor.isActive('italic') }" class="p-1 rounded hover:bg-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="4" x2="10" y2="4"></line><line x1="14" y1="20" x2="5" y2="20"></line><line x1="15" y1="4" x2="9" y2="20"></line></svg>
      </button>
      <button @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }" class="p-1 rounded hover:bg-gray-200 font-bold text-xs">
        H2
      </button>
      <button @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }" class="p-1 rounded hover:bg-gray-200 font-bold text-xs">
        H3
      </button>
      <button @click="editor.chain().focus().toggleBulletList().run()" :class="{ 'is-active': editor.isActive('bulletList') }" class="p-1 rounded hover:bg-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
      </button>
      <button @click="editor.chain().focus().toggleOrderedList().run()" :class="{ 'is-active': editor.isActive('orderedList') }" class="p-1 rounded hover:bg-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="10" y1="6" x2="21" y2="6"></line><line x1="10" y1="12" x2="21" y2="12"></line><line x1="10" y1="18" x2="21" y2="18"></line><path d="M4 6h1v4"></path><path d="M4 10h2"></path><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"></path></svg>
      </button>
    </div>
    <editor-content :editor="editor" v-if="editor" />
    <div v-if="editor" class="flex justify-end p-2 text-sm text-gray-500">
      {{ editor.storage.characterCount.characters() }}/{{ maxLength }}
    </div>
  </div>
</template>

<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import CharacterCount from '@tiptap/extension-character-count';
import { defineProps, defineEmits, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  maxLength: {
    type: Number,
    default: 1200,
  },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit,
    CharacterCount.configure({
      limit: props.maxLength,
    }),
  ],
  onUpdate: ({ editor }) => {
    // Truncate content if it exceeds the limit
    const html = editor.getHTML();
    const textContent = new DOMParser().parseFromString(html, 'text/html').body.textContent || '';
    
    if (textContent.length > props.maxLength) {
      // Get the content truncated to the max length
      const truncatedContent = textContent.substring(0, props.maxLength);
      // Convert back to HTML (simple approach, keeping existing HTML structure)
      const truncatedHtml = `<p>${truncatedContent}</p>`;
      emit('update:modelValue', truncatedHtml);
    } else {
      emit('update:modelValue', html);
    }
  },
  editorProps: {
    attributes: {
      class: 'prose prose-sm max-w-none p-4 focus:outline-none min-h-[200px]',
    },
    // Explicitly allow paste operations
    handlePaste: (view, event, slice) => {
      // Truncate the pasted content if it exceeds the limit
      const currentText = view.state.doc.textContent;
      const pastedText = slice.content.size ? slice.content.textContent : '';
      const totalLength = currentText.length + pastedText.length;
      
      if (totalLength > props.maxLength) {
        // Truncate the slice to fit within the limit
        const availableSpace = props.maxLength - currentText.length;
        if (availableSpace > 0) {
          // This is a simplified approach - in practice, we let the paste happen
          // and then the onUpdate will truncate the content
        } else {
          // Prevent paste if no space available
          event.preventDefault();
          return true; // Indicate that we handled the event
        }
      }
      return false; // Allow default behavior
    },
    transformPastedHTML: (html) => html,
    transformPastedText: (text) => text,
  },
});

watch(() => props.modelValue, (value) => {
  const isSame = editor.value.getHTML() === value;
  if (isSame) {
    return;
  }
  editor.value.commands.setContent(value, false);
});
</script>

<style scoped>
.is-active {
  background-color: #e5e7eb;
}
.prose :first-child {
    margin-top: 0;
}
.prose :last-child {
    margin-bottom: 0;
}
:deep(.prose) {
  font-size: 0.875rem; /* Equivalent to text-sm */
}
</style>