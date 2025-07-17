# PHPAgentFramework

**Lightweight PHP framework for building modular AI agents powered by LLMs.**  
Designed to help you prototype and simulate intelligent agents that can reason, plan, collaborate, and act using capabilities, tools, and memory — all from PHP.

---

## 🚀 What is this?

PHPAgentFramework allows you to build and orchestrate AI agents that:

- Use **LLMs (e.g. OpenAI GPT-4)** to generate reasoning, summaries, and plans
- Have defined **capabilities** and access to specific **tools**
- Maintain internal **memory**, which can be filtered or summarized for collaboration
- Support **planning-first execution** and coordination between agents
- Are structured using **PHP classes** for modularity and reuse

This is ideal for learning, experimentation, and early-stage simulations of multi-agent collaboration for those of us who love PHP.

The aim of the project is to end up with a Product / Project Manager that  will receive the indications for a software product, and the different agents will be able to create it based on the specifications from the user.

---

## 🧱 Features

- ✅ Base `Agent` class with planning, tool execution, and memory
- 🧠 Memory filtering and LLM-based summarization for collaboration
- 🔄 Logging of prompts, responses, and usage data
- 🧰 Modular tools + capabilities system
- 🧑‍💼 Support for specialized agents (e.g., Product Manager, Backend Dev, etc.)
- 🧪 Comes with example scripts to simulate coordination scenarios

---

## 📦 Requirements

- PHP 8.0+
- cURL enabled
- An OpenAI API Key (for examples using GPT)

---

## 🧪 Quickstart

```bash
git clone https://github.com/your-username/PHPAgentFramework.git
cd PHPAgentFramework
cp config.example.php config.php
# Add your OpenAI API key to config.php
php test_agent.php

