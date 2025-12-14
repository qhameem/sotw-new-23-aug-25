import mitt from 'mitt';
import { EVENT_TYPES, EVENT_PAYLOAD_SCHEMAS } from './eventTypes';

// Create the base emitter
const emitter = mitt();

// Validation function for event payloads
function validateEventPayload(eventType, payload) {
  const schema = EVENT_PAYLOAD_SCHEMAS[eventType];
  if (!schema) {
    // If no schema is defined for this event, skip validation
    return true;
  }

  // Validate each expected field in the schema
  for (const [fieldName, expectedType] of Object.entries(schema)) {
    const actualValue = payload[fieldName];
    if (actualValue !== undefined) { // Only validate if the field is provided
      let actualType = typeof actualValue;
      if (actualType === 'object' && Array.isArray(actualValue)) {
        actualType = 'array';
      }
      
      if (expectedType !== 'any' && actualType !== expectedType) {
        console.warn(`Event validation warning: Field '${fieldName}' expected type '${expectedType}', got '${actualType}' for event '${eventType}'`);
      }
    }
  }
  
  return true;
}

// Create a typed event emitter
const typedEventBus = {
  // Emit an event with optional payload validation
  emit(eventType, payload = null) {
    // Validate the payload if it's provided and validation is enabled
    if (payload !== null) {
      validateEventPayload(eventType, payload);
    }
    
    emitter.emit(eventType, payload);
  },

  // Listen to an event
  on(eventType, handler) {
    emitter.on(eventType, handler);
  },

  // Remove an event listener
  off(eventType, handler) {
    emitter.off(eventType, handler);
  },

  // Listen to an event only once
  once(eventType, handler) {
    const onceHandler = (payload) => {
      handler(payload);
      emitter.off(eventType, onceHandler);
    };
    emitter.on(eventType, onceHandler);
  },

  // Clear all handlers for an event
 clear(eventType) {
    emitter.all.delete(eventType);
  },

  // Get all registered event types
  getEventTypes() {
    return EVENT_TYPES;
  }
};

// Export the typed event bus and event types
export { typedEventBus, EVENT_TYPES };
export default typedEventBus;