import { ref, onMounted, onUnmounted } from 'vue';

export function useInputCaret(inputRef) {
  const caretStyle = ref(null);
  let frame = null;
  let observer;
  let composing = false;
  let context;

  const updateCaret = () => {
    frame = null;
    const input = inputRef.value;
    caretStyle.value = null;
    if (!input || document.activeElement !== input || composing
      || input.selectionStart === null || input.selectionStart !== input.selectionEnd) return;

    context ||= document.createElement('canvas').getContext('2d');
    if (!context) return;
    const style = getComputedStyle(input);
    context.font = `${style.fontStyle} ${style.fontWeight} ${style.fontSize} ${style.fontFamily}`;
    if ('letterSpacing' in context) context.letterSpacing = style.letterSpacing;
    const text = input.value.slice(0, input.selectionStart);
    const leftEdge = parseFloat(style.borderLeftWidth) + parseFloat(style.paddingLeft);
    const rightEdge = input.clientWidth + parseFloat(style.borderLeftWidth) - parseFloat(style.paddingRight);
    const left = leftEdge + context.measureText(text).width - input.scrollLeft;
    if (left < leftEdge || left > rightEdge) return;
    const height = parseFloat(style.fontSize);
    caretStyle.value = {
      left: `${Math.min(left, rightEdge - 2)}px`,
      top: `${(input.offsetHeight - height) / 2}px`,
      height: `${height}px`,
    };
  };

  const refreshCaret = (event) => {
    if (event?.type === 'compositionstart') composing = true;
    if (event?.type === 'compositionend') composing = false;
    if (frame !== null) cancelAnimationFrame(frame);
    frame = requestAnimationFrame(updateCaret);
  };

  onMounted(() => {
    document.addEventListener('selectionchange', refreshCaret);
    observer = new ResizeObserver(refreshCaret);
    if (inputRef.value) observer.observe(inputRef.value);
  });

  onUnmounted(() => {
    document.removeEventListener('selectionchange', refreshCaret);
    observer?.disconnect();
    if (frame !== null) cancelAnimationFrame(frame);
  });

  return { caretStyle, refreshCaret };
}
