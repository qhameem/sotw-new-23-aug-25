class TodoService {
  constructor(csrfToken, baseUrl) {
    this.csrfToken = csrfToken;
    this.baseUrl = baseUrl;
  }

  async apiCall(url, options) {
    const response = await fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': this.csrfToken,
        'Accept': 'application/json',
        ...options.headers
      }
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    return response.json();
  }

  async addItem(listId, title) {
    return await this.apiCall(`${this.baseUrl}/${listId}/items`, {
      method: 'POST',
      body: JSON.stringify({ 
        title: title, 
        color: 'gray' 
      })
    });
  }

  async updateItem(itemId, data) {
    return await this.apiCall(`${this.baseUrl}/items/${itemId}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  }

  async deleteItem(itemId) {
    return await this.apiCall(`${this.baseUrl}/items/${itemId}`, {
      method: 'DELETE'
    });
  }

  async changeItemColor(itemId, color) {
    return await this.apiCall(`${this.baseUrl}/items/${itemId}`, {
      method: 'PUT',
      body: JSON.stringify({ color: color })
    });
  }

 async updateItemDeadline(itemId, deadline) {
    return await this.apiCall(`${this.baseUrl}/items/${itemId}`, {
      method: 'PUT',
      body: JSON.stringify({ deadline: deadline })
    });
  }

 async saveItemTitle(itemId, title) {
    return await this.apiCall(`${this.baseUrl}/items/${itemId}`, {
      method: 'PUT',
      body: JSON.stringify({ title: title })
    });
  }

 async saveListTitle(listId, title) {
    return await this.apiCall(`${this.baseUrl}/${listId}`, {
      method: 'PATCH',
      body: JSON.stringify({ title: title })
    });
  }

 async createNewList(title) {
    return await this.apiCall(`${this.baseUrl}`, {
      method: 'POST',
      body: JSON.stringify({ title: title })
    });
  }

 async deleteList(listId) {
    return await this.apiCall(`${this.baseUrl}/${listId}`, {
      method: 'DELETE'
    });
  }
}

export default TodoService;