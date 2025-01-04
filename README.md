## Keycloak Client

1. Create a new client `wordpress-plugin` in the `tebuto-therapists` Realm
2. Set `Valid redirect URIs` to `*`
3. Add the `offline_access` scope to the client
4. Enable PKCE via the `Advanced Settings > Proof Key for Code Exchange Code Challenge Method > S256`
5. Set the login theme to `tebuto`
