#!/bin/bash
set -e

# Configuración
GITHUB_USER="chrisom79"
IMAGE="ghcr.io/${GITHUB_USER}/boletera-posadev"
TAG=$(date +%Y%m%d-%H%M)

echo "🔨 Buildeando imagen ${IMAGE}:${TAG}..."
docker build \
  -f Dockerfile.all-in-one \
  -t "${IMAGE}:latest" \
  -t "${IMAGE}:${TAG}" \
  .

echo "📤 Push a GHCR..."
docker push "${IMAGE}:latest"
docker push "${IMAGE}:${TAG}"

echo "✅ Imagen publicada:"
echo "   ${IMAGE}:latest"
echo "   ${IMAGE}:${TAG}"
echo ""
echo "En el servidor, corre: ~/deploy.sh"
