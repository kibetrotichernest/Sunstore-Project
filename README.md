# Sunstore-Project

graph TD
  A[Customer] -->|Browse| B[Product Catalog]
  A -->|Login/Register| C[User Account]
  A -->|Read| D[Blog/Projects]
  A -->|Add to Cart| E[Cart]
  E -->|Checkout| F[Order]
  F -->|Track| G[Order Tracking]
  A -->|Contact| H[Inquiries]
  A -->|Subscribe| I[Newsletter]
  J[Admin] -->|Manage| B
  J -->|Manage| F
  J -->|Manage| D
  J -->|View| K[Reports]
