const TEAM = [
  { name: "Team Member 1", role: "Project Lead", photo: "team_1761188674_1.png", color: "from-orange-400 to-orange-600" },
  { name: "Team Member 2", role: "Backend Developer", photo: "team_1761188674_3.png", color: "from-blue-400 to-blue-600" },
  { name: "Team Member 3", role: "Frontend Developer", photo: "team_1761188674_4.png", color: "from-purple-400 to-purple-600" },
  { name: "Team Member 4", role: "UI/UX Designer", photo: "team_1761188781_0.png", color: "from-green-400 to-green-600" },
  { name: "Team Member 5", role: "QA & Documentation", photo: "team_1761188781_2.png", color: "from-pink-400 to-pink-600" },
];

const GOALS = [
  "Build a fully functional e-commerce platform from the ground up",
  "Apply real-world database design and security practices",
  "Deliver a polished, production-style user experience",
];

const TECH = ["Next.js & React", "Supabase (Postgres, Auth, Storage, Realtime)", "Tailwind CSS", "Vercel"];

export default function AboutPage() {
  return (
    <main>
      <section className="bg-gradient-to-br from-brand-orange to-orange-600 px-4 py-20 text-center text-white">
        <h1 className="font-display text-3xl md:text-4xl">Meet the Creators Behind PetPantry+</h1>
        <p className="mx-auto mt-3 max-w-xl text-sm text-white/80">
          A student-built, full-stack pet supplies platform.
        </p>
        <span className="mt-6 inline-block rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold backdrop-blur-sm">
          🎓 Academic Portfolio Project 2026
        </span>
      </section>

      <section className="mx-auto max-w-6xl px-4 py-16">
        <h2 className="text-center text-2xl font-bold text-neutral-900">Our Team</h2>
        <p className="mt-2 text-center text-sm text-neutral-500">The people who built PetPantry+</p>
        <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-5">
          {TEAM.map((member) => (
            <div
              key={member.name}
              className="overflow-hidden rounded-2xl border-2 border-neutral-100 shadow-lg transition-transform hover:-translate-y-2"
            >
              <div className={`h-64 bg-gradient-to-br ${member.color}`}>
                <img src={`/team/${member.photo}`} alt={member.name} className="h-full w-full object-cover" />
              </div>
              <div className="p-4 text-center">
                <p className="text-lg font-bold text-neutral-900">{member.name}</p>
                <span className="mt-1 inline-block rounded-full bg-neutral-100 px-3 py-1 text-xs font-semibold text-neutral-600">
                  {member.role}
                </span>
              </div>
            </div>
          ))}
        </div>
      </section>

      <section className="bg-gradient-to-br from-neutral-50 to-neutral-100 px-4 py-16">
        <div className="mx-auto grid max-w-4xl grid-cols-1 gap-6 sm:grid-cols-3">
          <div className="rounded-xl bg-white p-8 text-center shadow-lg">
            <p className="text-3xl">🎓</p>
            <p className="mt-2 font-bold text-brand-orange">Portfolio Project</p>
            <p className="text-sm text-neutral-500">Full-stack development showcase</p>
          </div>
          <div className="rounded-xl bg-white p-8 text-center shadow-lg">
            <p className="text-3xl">💻</p>
            <p className="mt-2 font-bold text-brand-orange">Capstone Build</p>
            <p className="text-sm text-neutral-500">Next.js + Supabase</p>
          </div>
          <div className="rounded-xl bg-white p-8 text-center shadow-lg">
            <p className="text-3xl">📅</p>
            <p className="mt-2 font-bold text-brand-orange">2026</p>
            <p className="text-sm text-neutral-500">Built and shipped</p>
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-4xl px-4 py-16">
        <div className="rounded-2xl border-2 border-orange-100 bg-white p-8 shadow-xl">
          <div className="grid grid-cols-1 gap-8 md:grid-cols-2">
            <div>
              <h3 className="mb-3 font-bold text-neutral-900">🎯 Project Goals</h3>
              <ul className="flex flex-col gap-2 text-sm text-neutral-600">
                {GOALS.map((goal) => (
                  <li key={goal} className="flex gap-2">
                    <span className="text-brand-orange">•</span> {goal}
                  </li>
                ))}
              </ul>
            </div>
            <div>
              <h3 className="mb-3 font-bold text-neutral-900">⚙️ Technologies Used</h3>
              <ul className="flex flex-col gap-2 text-sm text-neutral-600">
                {TECH.map((tech) => (
                  <li key={tech} className="flex gap-2">
                    <span className="text-brand-orange">•</span> {tech}
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}
