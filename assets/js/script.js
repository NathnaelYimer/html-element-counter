/**
 * HTML Element Counter - Client-side JavaScript
 * Handles form submission, AJAX requests, and UI updates
 */

class ElementCounterApp {
  constructor() {
    this.form = document.getElementById("elementCounterForm")
    this.submitBtn = document.getElementById("submitBtn")
    this.btnText = document.querySelector(".btn-text")
    this.btnLoader = document.getElementById("btnLoader")
    this.resultsSection = document.getElementById("results")
    this.errorSection = document.getElementById("errorSection")
    this.cacheIndicator = document.getElementById("cacheIndicator")

    this.init()
  }

  init() {
    this.bindEvents()
    this.setupFormValidation()
  }

  bindEvents() {
    // Form submission
    this.form.addEventListener("submit", (e) => {
      e.preventDefault()
      this.handleSubmit()
    })

    // Real-time validation
    document.getElementById("url").addEventListener("input", () => {
      this.validateField("url")
    })

    document.getElementById("element").addEventListener("input", () => {
      this.validateField("element")
    })

    // Clear errors on focus
    document.getElementById("url").addEventListener("focus", () => {
      this.clearFieldError("url")
    })

    document.getElementById("element").addEventListener("focus", () => {
      this.clearFieldError("element")
    })
  }

  setupFormValidation() {
    // Add custom validation messages
    const urlInput = document.getElementById("url")
    const elementInput = document.getElementById("element")

    urlInput.addEventListener("invalid", (e) => {
      e.preventDefault()
      this.showFieldError("url", "Please enter a valid URL (e.g., https://example.com)")
    })

    elementInput.addEventListener("invalid", (e) => {
      e.preventDefault()
      this.showFieldError("element", "Please enter a valid HTML element name (e.g., img, div, p)")
    })
  }

  async handleSubmit() {
    // Clear previous results and errors
    this.hideResults()
    this.hideError()

    // Get form data
    const formData = new FormData(this.form)
    const url = formData.get("url").trim()
    const element = formData.get("element").trim().toLowerCase()

    // Client-side validation
    if (!this.validateForm(url, element)) {
      return
    }

    // Show loading state
    this.setLoadingState(true)

    try {
      const response = await this.makeRequest(url, element)

      if (response.success) {
        this.displayResults(response)
      } else {
        this.displayError(response.error)
      }
    } catch (error) {
      console.error("Request failed:", error)
      this.displayError("Network error. Please check your connection and try again.")
    } finally {
      this.setLoadingState(false)
    }
  }

  validateForm(url, element) {
    let isValid = true

    // Validate URL
    if (!url) {
      this.showFieldError("url", "URL is required")
      isValid = false
    } else if (!this.isValidUrl(url)) {
      this.showFieldError("url", "Please enter a valid URL")
      isValid = false
    }

    // Validate element
    if (!element) {
      this.showFieldError("element", "Element name is required")
      isValid = false
    } else if (!this.isValidElementName(element)) {
      this.showFieldError("element", "Please enter a valid HTML element name")
      isValid = false
    }

    return isValid
  }

  validateField(fieldName) {
    const field = document.getElementById(fieldName)
    const value = field.value.trim()

    if (fieldName === "url" && value) {
      if (!this.isValidUrl(value)) {
        this.showFieldError("url", "Please enter a valid URL")
        return false
      } else {
        this.clearFieldError("url")
        return true
      }
    }

    if (fieldName === "element" && value) {
      if (!this.isValidElementName(value)) {
        this.showFieldError("element", "Please enter a valid HTML element name")
        return false
      } else {
        this.clearFieldError("element")
        return true
      }
    }

    return true
  }

  isValidUrl(url) {
    try {
      // Add protocol if missing
      if (!url.match(/^https?:\/\//)) {
        url = "http://" + url
      }

      const urlObj = new URL(url)
      return ["http:", "https:"].includes(urlObj.protocol)
    } catch {
      return false
    }
  }

  isValidElementName(element) {
    return /^[a-zA-Z][a-zA-Z0-9]*$/.test(element) && element.length <= 20
  }

  async makeRequest(url, element) {
    const response = await fetch("api/process.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ url, element }),
    })

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.error || "Request failed")
    }

    return await response.json()
  }

  displayResults(data) {
    const { result, statistics, cached } = data

    // Show cache indicator
    if (cached) {
      this.cacheIndicator.innerHTML = '<span class="cache-badge">Cached Result</span>'
      this.cacheIndicator.style.display = "block"
    } else {
      this.cacheIndicator.style.display = "none"
    }

    // Display request results
    const requestResults = document.getElementById("requestResults")
    requestResults.innerHTML = `
            <div class="result-item">
                <strong>URL:</strong> <a href="${this.escapeHtml(result.url)}" target="_blank" rel="noopener">${this.escapeHtml(result.url)}</a>
            </div>
            <div class="result-item">
                <strong>Fetched on:</strong> ${result.timestamp}, took ${result.fetch_time}ms
            </div>
            <div class="result-item highlight">
                <strong>Element <code>&lt;${result.element}&gt;</code> appeared ${result.count} time${result.count !== 1 ? "s" : ""} in page.</strong>
            </div>
        `

    // Display statistics
    const statisticsResults = document.getElementById("statisticsResults")
    const domain = new URL(result.url.startsWith("http") ? result.url : "http://" + result.url).hostname

    statisticsResults.innerHTML = `
            <div class="stat-item">
                <strong>${statistics.domain_urls}</strong> different URLs from <strong>${domain}</strong> have been fetched
            </div>
            <div class="stat-item">
                Average fetch time from <strong>${domain}</strong> during the last 24 hours is <strong>${statistics.domain_avg_time}ms</strong>
            </div>
            <div class="stat-item">
                There was a total of <strong>${statistics.domain_element_total}</strong> <code>&lt;${result.element}&gt;</code> elements from <strong>${domain}</strong>
            </div>
            <div class="stat-item">
                Total of <strong>${statistics.global_element_total}</strong> <code>&lt;${result.element}&gt;</code> elements counted in all requests ever made
            </div>
        `

    this.showResults()
  }

  displayError(errorMessage) {
    const errorMessageEl = document.getElementById("errorMessage")
    errorMessageEl.textContent = errorMessage
    this.showError()
  }

  showFieldError(fieldName, message) {
    const errorEl = document.getElementById(fieldName + "Error")
    const fieldEl = document.getElementById(fieldName)

    errorEl.textContent = message
    errorEl.style.display = "block"
    fieldEl.classList.add("error")
  }

  clearFieldError(fieldName) {
    const errorEl = document.getElementById(fieldName + "Error")
    const fieldEl = document.getElementById(fieldName)

    errorEl.style.display = "none"
    fieldEl.classList.remove("error")
  }

  setLoadingState(loading) {
    if (loading) {
      this.submitBtn.disabled = true
      this.submitBtn.classList.add("loading")
      this.btnText.textContent = "Processing..."
      this.btnLoader.style.display = "inline-block"
    } else {
      this.submitBtn.disabled = false
      this.submitBtn.classList.remove("loading")
      this.btnText.textContent = "Count Elements"
      this.btnLoader.style.display = "none"
    }
  }

  showResults() {
    this.resultsSection.style.display = "block"
    this.errorSection.style.display = "none"

    // Smooth scroll to results
    this.resultsSection.scrollIntoView({
      behavior: "smooth",
      block: "start",
    })
  }

  hideResults() {
    this.resultsSection.style.display = "none"
  }

  showError() {
    this.errorSection.style.display = "block"
    this.resultsSection.style.display = "none"

    // Smooth scroll to error
    this.errorSection.scrollIntoView({
      behavior: "smooth",
      block: "start",
    })
  }

  hideError() {
    this.errorSection.style.display = "none"
  }

  escapeHtml(text) {
    const div = document.createElement("div")
    div.textContent = text
    return div.innerHTML
  }
}

// Initialize the application when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  new ElementCounterApp()
})

// Add some helpful keyboard shortcuts
document.addEventListener("keydown", (e) => {
  // Ctrl/Cmd + Enter to submit form
  if ((e.ctrlKey || e.metaKey) && e.key === "Enter") {
    const form = document.getElementById("elementCounterForm")
    if (form) {
      form.dispatchEvent(new Event("submit"))
    }
  }
})
