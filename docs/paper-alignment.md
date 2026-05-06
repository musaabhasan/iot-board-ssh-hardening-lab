# Paper Alignment

This project is based on:

Alfandi, O., Hasan, M., & Balbahaith, Z. (2019). **Assessment and Hardening of IoT Development Boards**. In M. Di Felice, E. Natalizio, R. Bruno, & A. Kassler (Eds.), *Wired/Wireless Internet Communications, WWIC 2019*, Lecture Notes in Computer Science, vol. 11618, pp. 27-39. Springer, Cham. https://doi.org/10.1007/978-3-030-30523-9_3

## Research Themes Implemented

| Paper theme | Repository implementation |
| --- | --- |
| SSH is a common management protocol for development boards | Assessment model focuses on SSH configuration, authentication, and exposure. |
| SSH V1/V2 compatibility can create downgrade risk | `ssh-v1-downgrade` threat and `ssh-protocol-2` control. |
| First-boot key generation can be weak when entropy is insufficient | `weak-first-boot-keys` threat, host-key regeneration control, and hardware RNG control. |
| ARP poisoning can enable local man-in-the-middle positioning | `arp-mitm` threat, network segmentation control, and MITM monitoring control. |
| Default operating-system images can include unnecessary packages and services | Default image attack-surface threat, package minimization, service baseline, and image hardening. |
| OpenSSH versions require vulnerability-aware maintenance | Patch currency, version baseline, and vulnerability tracking controls. |
| Public-key trust should be validated | Signed-key or trusted key authority control with key inventory and change control. |

## Scenario Model

The paper compares SSH implementation variations during man-in-the-middle conditions. The repository models these as:

- Legacy SSH V1 and V2 enabled.
- SSH V2 with password administration.
- SSH V2 with public-key administration.
- Hardened SSH with regenerated and signed keys.

## Deliberate Boundaries

The repository is an assessment and hardening platform. It does not reproduce offensive experiment tooling. It captures the paper's defensive conclusions as controls, evidence expectations, and risk calculations that can be used in authorized labs and operational assurance workflows.

